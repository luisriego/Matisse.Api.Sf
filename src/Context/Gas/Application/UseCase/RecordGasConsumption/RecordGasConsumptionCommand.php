<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\RecordGasConsumption;

use App\Shared\Application\Command;

final readonly class RecordGasConsumptionCommand implements Command
{
    public function __construct(
        private string $id,
        private string $residentUnitId,
        private int $year,
        private int $month,
        private float $consumption
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getResidentUnitId(): string
    {
        return $this->residentUnitId;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function getConsumption(): float
    {
        return $this->consumption;
    }
}