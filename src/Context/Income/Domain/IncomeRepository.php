<?php

declare(strict_types=1);

namespace App\Context\Income\Domain;

use App\Shared\Domain\ValueObject\DateRange;

interface IncomeRepository
{
    public function flush(): void;

    public function save(Income $income, bool $flush = true): void;

    public function findOneByIdOrFail(string $id): Income;

    public function findAll(): array;

    public function findActiveByDateRange(DateRange $dateRange): array;

    public function findInactiveByDateRange(DateRange $dateRange): array;

    /**
     * All incomes whose dueDate falls within the range (active or not).
     *
     * @return Income[]
     */
    public function findByDueDateInRange(DateRange $dateRange): array;

    /**
     * Incomes whose dueDate falls in the range (inclusive), any active flag.
     */
    public function countInDueDateRange(DateRange $dateRange): int;

    /**
     * Incomes in range with a non-empty description (memo text for credit SQL history matching).
     */
    public function countWithNonEmptyDescriptionInDueDateRange(DateRange $dateRange): int;
}
