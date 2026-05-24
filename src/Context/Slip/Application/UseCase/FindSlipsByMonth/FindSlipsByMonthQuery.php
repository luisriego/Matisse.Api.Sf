<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\FindSlipsByMonth;

use App\Shared\Application\Query;

final readonly class FindSlipsByMonthQuery implements Query
{
    public function __construct(
        private int $year,
        private int $month,
    ) {}

    public function year(): int
    {
        return $this->year;
    }

    public function month(): int
    {
        return $this->month;
    }
}
