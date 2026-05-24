<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain;

interface SlipGenerationParameterSnapshotRepository
{
    public function findByExpenseMonth(int $expenseYear, int $expenseMonth): ?SlipGenerationParameterSnapshot;

    public function upsertForExpenseMonth(
        int $expenseYear,
        int $expenseMonth,
        int $extraFeePerUnitCents,
        int $reserveFundPerUnitCents,
    ): void;

    public function flush(): void;
}
