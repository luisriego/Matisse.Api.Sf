<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

use function array_map;

final class VerifyIncomeRequestDto implements RequestDto
{
    public readonly int $month;
    public readonly int $year;

    /** @var array<array{importLineKey: string, amountInCents: int, memo: string}> */
    public readonly array $creditLines;

    public function __construct(Request $request)
    {
        $data = $request->toArray();

        $this->month = (int) ($data['month'] ?? 0);
        $this->year  = (int) ($data['year']  ?? 0);

        $this->creditLines = array_map(
            static fn (array $line) => [
                'importLineKey' => (string) ($line['importLineKey'] ?? $line['fitId'] ?? ''),
                'amountInCents' => (int) ($line['amountInCents'] ?? 0),
                'memo'          => (string) ($line['memo'] ?? ''),
            ],
            $data['creditLines'] ?? [],
        );
    }
}
