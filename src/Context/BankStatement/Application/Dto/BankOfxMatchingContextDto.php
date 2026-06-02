<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Dto;

use App\Context\BankStatement\Infrastructure\Http\Controller\OfxMatchingContextGetController;

/**
 * DB-backed signals for OFX line matching (same rolling window as expense/income history matchers).
 *
 * Uses server "today" as the window end — not calendar "first month"; reliable counts only from persisted data.
 *
 * @see OfxMatchingContextGetController for OpenAPI shape
 */
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
