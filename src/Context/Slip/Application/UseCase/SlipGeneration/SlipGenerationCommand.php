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
}
