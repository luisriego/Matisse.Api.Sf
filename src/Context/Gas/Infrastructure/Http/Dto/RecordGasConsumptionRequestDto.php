<?php

declare(strict_types=1);

namespace App\Context\Gas\Infrastructure\Http\Dto;

use App\Context\Gas\Application\UseCase\RecordGasConsumption\RecordGasConsumptionCommand;

final readonly class RecordGasConsumptionRequestDto
{
    public function __construct(
        public string $id,
        public string $residentUnitId,
        public int $year,
        public int $month,
        public float $consumption
    ) {
    }

    public function toCommand(): RecordGasConsumptionCommand
    {
        return new RecordGasConsumptionCommand(
            $this->id,
            $this->residentUnitId,
            $this->year,
            $this->month,
            $this->consumption
        );
    }
}