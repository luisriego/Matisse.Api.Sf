<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\FindGasReading;

use App\Context\ResidentUnit\Domain\ResidentUnitId; // Importar
use App\Shared\Application\Query;
use App\Shared\Domain\ValueObject\Month;
use App\Shared\Domain\ValueObject\Year;

final readonly class FindGasReadingQuery implements Query
{
    public function __construct(
        public ResidentUnitId $residentUnitId,
        public Year $year,
        public Month $month,
    ) {}
}
