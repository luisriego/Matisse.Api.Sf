<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\UseCase\ConfirmBankOfxLines;

use App\Context\BankStatement\Application\Dto\ExpectedExpenseSpecDto;

/**
 * Carries the data that the user confirmed for a single bank transaction line.
 *
 * lineType: "expense" (DEBIT) | "income" (CREDIT).
 * For expense lines, expenseTypeId is required.
 * For income  lines, incomeTypeId is optional (a default may be configured per environment).
 */
final readonly class ConfirmLineDto
{
    public const TYPE_EXPENSE = 'expense';
    public const TYPE_INCOME  = 'income';

    /** Income line that represents the settlement of monthly boletos (consolidated + validated). */
    public const CREDIT_KIND_BOLETO_SETTLEMENT = 'boleto_settlement';
    /** Income line that represents other credits (interest, refunds, etc.): 1 income per line, no validation. */
    public const CREDIT_KIND_OTHER             = 'other';

    public function __construct(
        public readonly string  $importLineKey,
        public readonly int     $amountInCents,
        public readonly string  $postedAt,
        public readonly string  $memo,
        public readonly string  $accountId,
        public readonly string  $dueDate,
        public readonly string  $lineType           = self::TYPE_EXPENSE,
        public readonly ?string $expenseTypeId      = null,
        public readonly ?string $incomeTypeId       = null,
        public readonly ?string $description        = null,
        public readonly ?string $recurringExpenseId = null,
        public readonly ?string $residentUnitId     = null,
        public readonly string  $creditKind         = self::CREDIT_KIND_BOLETO_SETTLEMENT,
        public readonly bool    $isExpectedExpense  = true,
        public readonly ?ExpectedExpenseSpecDto $expectedExpense = null,
    ) {}

    public function isIncome(): bool
    {
        return $this->lineType === self::TYPE_INCOME;
    }

    public function isBoletoSettlement(): bool
    {
        return $this->isIncome() && $this->creditKind === self::CREDIT_KIND_BOLETO_SETTLEMENT;
    }

    public function isOtherCredit(): bool
    {
        return $this->isIncome() && $this->creditKind === self::CREDIT_KIND_OTHER;
    }
}
