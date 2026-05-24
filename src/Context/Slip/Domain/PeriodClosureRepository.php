<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain;

interface PeriodClosureRepository
{
    public function save(PeriodClosure $closure, bool $flush = true): void;

    public function existsForMonth(int $year, int $month): bool;

    public function findByMonth(int $year, int $month): ?PeriodClosure;

    public function deleteByMonth(int $year, int $month): void;
}
