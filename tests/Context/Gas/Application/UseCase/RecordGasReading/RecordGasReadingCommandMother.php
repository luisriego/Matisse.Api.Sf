<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Application\UseCase\RecordGasReading;

use App\Context\Gas\Application\UseCase\RecordGasReading\RecordGasReadingCommand;
use App\Tests\Shared\Domain\UuidMother;

final class RecordGasReadingCommandMother
{
    public static function create(
        ?string $id = null,
        ?string $residentUnitId = null,
        ?int $year = null,
        ?int $month = null,
        ?float $reading = null,
    ): RecordGasReadingCommand {
        return new RecordGasReadingCommand(
            $id ?? UuidMother::create(),
            $residentUnitId ?? UuidMother::create(),
            $year ?? 2024,
            $month ?? 5,
            $reading ?? 1234.56,
        );
    }
}
