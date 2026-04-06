<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Dto;

/**
 * Represents how a bank transaction was previously categorised in a past month.
 * Returned inside each TransactionPreviewDto to assist the user in repeating the same assignment.
 */
final readonly class PastAssignmentDto
{
    public function __construct(
        public readonly int $month,
        public readonly int $year,
        public readonly int $amountInCents,
        public readonly ?string $expenseTypeId,
        public readonly ?string $expenseTypeName,
        public readonly ?string $recurringExpenseId,
        public readonly ?string $recurringExpenseName,
        public readonly ?string $accountId,
        public readonly ?string $residentUnitId,
        public readonly float $confidence,
    ) {}
}
