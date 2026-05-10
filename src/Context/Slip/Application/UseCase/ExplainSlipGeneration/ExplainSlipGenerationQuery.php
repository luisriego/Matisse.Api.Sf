<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\ExplainSlipGeneration;

use App\Shared\Application\Query;

final readonly class ExplainSlipGenerationQuery implements Query
{
    public function __construct(
        private int $year,
        private int $month,
        private int $extraFeePerUnitCents = 0,
        private int $reserveFundPerUnitCents = 0,
    ) {}

    public function year(): int
    {
        return $this->year;
    }

    public function month(): int
    {
        return $this->month;
    }

    public function extraFeePerUnitCents(): int
    {
        return $this->extraFeePerUnitCents;
    }

    public function reserveFundPerUnitCents(): int
    {
        return $this->reserveFundPerUnitCents;
    }
}
