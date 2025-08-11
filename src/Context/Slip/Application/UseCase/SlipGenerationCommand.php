<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase;

use App\Shared\Application\Command;

final readonly class SlipGenerationCommand implements Command
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