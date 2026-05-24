<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Http\Dto;

use App\Context\Slip\Application\UseCase\ImportConsolidatedSlips\ImportConsolidatedSlipsCommand;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

use function array_map;
use function explode;
use function is_array;
use function is_string;
use function preg_match;

class ImportConsolidatedSlipsRequestDto implements RequestDto
{
    public readonly int $year;
    public readonly int $month;

    /** @var list<array{residentUnitId: string, amountCents: int, components?: array<string, int>}> */
    public readonly array $slips;

    public function __construct(Request $request)
    {
        $payload = $request->toArray();

        $monthValue = $payload['targetMonth'] ?? null;
        if (!is_string($monthValue) || 1 !== preg_match('/^\d{4}-\d{2}$/', $monthValue)) {
            throw new InvalidArgumentException('Invalid targetMonth. Expected YYYY-MM.');
        }

        [$year, $month] = array_map('intval', explode('-', $monthValue));

        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Invalid month. Must be between 01 and 12.');
        }

        $this->year = $year;
        $this->month = $month;

        $rawSlips = $payload['slips'] ?? null;
        if (!is_array($rawSlips) || $rawSlips === []) {
            throw new InvalidArgumentException('slips array is required and must not be empty.');
        }

        $parsed = [];
        foreach ($rawSlips as $i => $entry) {
            if (!is_array($entry) || !isset($entry['residentUnitId'], $entry['amountCents'])) {
                throw new InvalidArgumentException("slips[$i] must have residentUnitId and amountCents.");
            }

            $item = [
                'residentUnitId' => (string) $entry['residentUnitId'],
                'amountCents' => (int) $entry['amountCents'],
            ];

            if (isset($entry['components']) && is_array($entry['components'])) {
                $item['components'] = $entry['components'];
            }

            $parsed[] = $item;
        }

        $this->slips = $parsed;
    }

    public function toCommand(): ImportConsolidatedSlipsCommand
    {
        return new ImportConsolidatedSlipsCommand(
            $this->year,
            $this->month,
            $this->slips,
        );
    }
}
