<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Dto;

use OpenApi\Attributes as OA;

/**
 * DB-backed signals for OFX line matching (same rolling window as expense/income history matchers).
 *
 * Uses server "today" as the window end — not calendar "first month"; reliable counts only from persisted data.
 */
#[OA\Schema(
    schema: 'BankOfxMatchingContext',
    properties: [
        new OA\Property(property: 'historyWindowMonths', type: 'integer', example: 12,
            description: 'Months looked back (aligned with SQL history matchers).'),
        new OA\Property(property: 'windowStartDate', type: 'string', format: 'date', example: '2025-04-19'),
        new OA\Property(property: 'windowEndDate', type: 'string', format: 'date', example: '2026-04-19'),
        new OA\Property(property: 'activeExpenseCountInWindow', type: 'integer', example: 42),
        new OA\Property(property: 'activeExpenseWithDescriptionCountInWindow', type: 'integer', example: 30,
            description: 'Active expenses with memo text usable for SQL similarity matching.'),
        new OA\Property(property: 'incomeRecordedCountInWindow', type: 'integer', example: 12),
        new OA\Property(property: 'incomeWithDescriptionCountInWindow', type: 'integer', example: 8,
            description: 'Incomes with memo text usable for credit SQL similarity matching.'),
        new OA\Property(property: 'expenseEmbeddingIndexedCount', type: 'integer', example: 120,
            description: 'Rows in expense_embedding (pgvector semantic debit hints).'),
        new OA\Property(property: 'debitSqlHistoryAvailable', type: 'boolean',
            description: 'True when at least one active expense with description exists in the window.'),
        new OA\Property(property: 'debitSemanticIndexAvailable', type: 'boolean'),
        new OA\Property(property: 'creditSqlHistoryAvailable', type: 'boolean'),
        new OA\Property(property: 'manualDebitClassificationExpected', type: 'boolean',
            description: 'True when neither SQL debit history nor semantic index can assist (typical greenfield).'),
    ],
)]
final readonly class BankOfxMatchingContextDto
{
    public function __construct(
        public int $historyWindowMonths,
        public string $windowStartDate,
        public string $windowEndDate,
        public int $activeExpenseCountInWindow,
        public int $activeExpenseWithDescriptionCountInWindow,
        public int $incomeRecordedCountInWindow,
        public int $incomeWithDescriptionCountInWindow,
        public int $expenseEmbeddingIndexedCount,
        public bool $debitSqlHistoryAvailable,
        public bool $debitSemanticIndexAvailable,
        public bool $creditSqlHistoryAvailable,
        public bool $manualDebitClassificationExpected,
    ) {}
}
