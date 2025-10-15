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
}
