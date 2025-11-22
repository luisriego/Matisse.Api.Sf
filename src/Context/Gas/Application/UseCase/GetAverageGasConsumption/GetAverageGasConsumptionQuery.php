<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\GetAverageGasConsumption;

use App\Shared\Application\Query;

final readonly class GetAverageGasConsumptionQuery implements Query
{
    public function __construct(
        public string $residentUnitId,
    ) {}
}
