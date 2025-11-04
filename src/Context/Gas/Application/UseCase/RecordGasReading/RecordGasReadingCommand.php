<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\RecordGasReading;

use App\Shared\Application\Command;

final readonly class RecordGasReadingCommand implements Command
{
    public function __construct(
        private string $id,
        private string $residentUnitId,
        private int $year,
        private int $month,
        private float $reading,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function residentUnitId(): string
    {
        return $this->residentUnitId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function month(): int
    {
        return $this->month;
    }

    public function reading(): float
    {
        return $this->reading;
    }
}
