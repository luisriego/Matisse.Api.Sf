<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain;

use App\Shared\Domain\ValueObject\DateRange;

interface ExpenseRepository
{
    public function flush(): void;

    public function save(Expense $expense, bool $flush = true): void;

    public function remove(Expense $expense, bool $flush = true): void;

    public function findOneByIdOrFail(string $id): Expense;

    public function findOneById(string $id): ?Expense;

    public function findAll(): array;

    public function findActiveByDateRange(DateRange $dateRange): array;

    public function findInactiveByDateRange(DateRange $dateRange): array;

    public function findByRecurringExpenseAndMonthYear(string $recurringExpenseId, int $month, int $year): ?Expense;

    /** Active expenses whose dueDate falls in the range (inclusive). */
    public function countActiveInDueDateRange(DateRange $dateRange): int;

    /**
     * Active expenses in range with a non-empty description (memo text for SQL history matching).
     */
    public function countActiveWithNonEmptyDescriptionInDueDateRange(DateRange $dateRange): int;
}
