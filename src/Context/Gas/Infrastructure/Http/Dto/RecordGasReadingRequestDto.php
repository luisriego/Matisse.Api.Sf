<?php

declare(strict_types=1);

namespace App\Context\Gas\Infrastructure\Http\Dto;

use App\Context\Gas\Application\UseCase\RecordGasReading\RecordGasReadingCommand;

final readonly class RecordGasReadingRequestDto
{
    public function __construct(
        public string $id,
        public string $residentUnitId,
        public int $year,
        public int $month,
        public float $reading,
    ) {}

    public function toCommand(): RecordGasReadingCommand
    {
        return new RecordGasReadingCommand(
            $this->id,
            $this->residentUnitId,
            $this->year,
            $this->month,
            $this->reading,
        );
    }
}
