<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Http\Dto;

use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

final class ConfirmBankOfxLinesRequestDto implements RequestDto
{
    public readonly string $bankAccountId;

    /** @var ConfirmLineRequestDto[] */
    public readonly array $lines;

    public readonly ?int $settlementExtraFeePerUnitCents;

    public readonly ?int $settlementReserveFundPerUnitCents;

    public function __construct(Request $request)
    {
        $data = $request->toArray();

        $this->bankAccountId = (string) ($data['bankAccountId'] ?? '');
        $this->settlementExtraFeePerUnitCents = isset($data['settlementExtraFeePerUnitCents'])
            ? (int) $data['settlementExtraFeePerUnitCents'] : null;
        $this->settlementReserveFundPerUnitCents = isset($data['settlementReserveFundPerUnitCents'])
            ? (int) $data['settlementReserveFundPerUnitCents'] : null;

        $this->lines = array_map(
            static fn (array $line) => new ConfirmLineRequestDto(
                fitId:              (string) ($line['fitId'] ?? ''),
                amountInCents:      (int) ($line['amountInCents'] ?? 0),
                postedAt:           (string) ($line['postedAt'] ?? ''),
                memo:               (string) ($line['memo'] ?? ''),
                accountId:          (string) ($line['accountId'] ?? ''),
                dueDate:            (string) ($line['dueDate'] ?? ''),
                lineType:           (string) ($line['lineType'] ?? 'expense'),
                expenseTypeId:      isset($line['expenseTypeId'])      ? (string) $line['expenseTypeId']      : null,
                incomeTypeId:       isset($line['incomeTypeId'])       ? (string) $line['incomeTypeId']       : null,
                description:        isset($line['description'])        ? (string) $line['description']        : null,
                recurringExpenseId: isset($line['recurringExpenseId']) ? (string) $line['recurringExpenseId'] : null,
                residentUnitId:     isset($line['residentUnitId'])     ? (string) $line['residentUnitId']     : null,
                creditKind:         (string) ($line['creditKind'] ?? 'boleto_settlement'),
            ),
            $data['lines'] ?? [],
        );
    }
}
