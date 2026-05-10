<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\SlipGeneration;

use App\Shared\Application\Command;

final readonly class SlipGenerationCommand implements Command
{
    public function __construct(
        private int $year,
        private int $month,
        private bool $isForced = false,
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

    public function isForced(): bool
    {
        return $this->isForced;
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
