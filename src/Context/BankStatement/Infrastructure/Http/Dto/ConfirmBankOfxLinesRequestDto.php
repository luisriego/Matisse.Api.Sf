<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Http\Dto;

use App\Context\BankStatement\Application\Dto\ExpectedExpenseCreateOrUpdateDto;
use App\Context\BankStatement\Application\Dto\ExpectedExpenseSpecDto;
use App\Shared\Infrastructure\RequestDto;
use Symfony\Component\HttpFoundation\Request;

use function array_map;
use function is_array;

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
                importLineKey: (string) ($line['importLineKey'] ?? $line['fitId'] ?? ''),
                amountInCents: (int) ($line['amountInCents'] ?? 0),
                postedAt: (string) ($line['postedAt'] ?? ''),
                memo: (string) ($line['memo'] ?? ''),
                accountId: (string) ($line['accountId'] ?? ''),
                dueDate: (string) ($line['dueDate'] ?? ''),
                lineType: (string) ($line['lineType'] ?? 'expense'),
                expenseTypeId: isset($line['expenseTypeId']) ? (string) $line['expenseTypeId'] : null,
                incomeTypeId: isset($line['incomeTypeId']) ? (string) $line['incomeTypeId'] : null,
                description: isset($line['description']) ? (string) $line['description'] : null,
                recurringExpenseId: isset($line['recurringExpenseId']) ? (string) $line['recurringExpenseId'] : null,
                residentUnitId: isset($line['residentUnitId']) ? (string) $line['residentUnitId'] : null,
                creditKind: (string) ($line['creditKind'] ?? 'boleto_settlement'),
                isExpectedExpense: !isset($line['isExpectedExpense']) || (bool) $line['isExpectedExpense'],
                expectedExpense: isset($line['expectedExpense']) && is_array($line['expectedExpense'])
                    ? self::parseExpectedExpense($line['expectedExpense']) : null,
            ),
            $data['lines'] ?? [],
        );
    }

    private static function parseExpectedExpense(array $raw): ExpectedExpenseSpecDto
    {
        $recurringId = isset($raw['recurringExpenseId']) ? (string) $raw['recurringExpenseId'] : null;

        if ($recurringId === '') {
            $recurringId = null;
        }

        $createOrUpdate = null;

        if (isset($raw['createOrUpdate']) && is_array($raw['createOrUpdate'])) {
            $cu = $raw['createOrUpdate'];
            $months = isset($cu['monthsOfYear']) && is_array($cu['monthsOfYear'])
                ? array_map('intval', $cu['monthsOfYear']) : null;

            $createOrUpdate = new ExpectedExpenseCreateOrUpdateDto(
                displayName: (string) ($cu['displayName'] ?? ''),
                frequency: (string) ($cu['frequency'] ?? 'monthly'),
                amountKind: (string) ($cu['amountKind'] ?? 'variable'),
                monthsOfYear: $months,
                dueDay: isset($cu['dueDay']) ? (int) $cu['dueDay'] : null,
            );
        }

        return new ExpectedExpenseSpecDto($recurringId, $createOrUpdate);
    }
}
