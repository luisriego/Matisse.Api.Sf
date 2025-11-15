<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\FindLastGasReading;

use App\Shared\Application\Query;

final readonly class FindLastGasReadingQuery implements Query
{
    public function __construct(
        public string $residentUnitId,
    ) {
    }
}
