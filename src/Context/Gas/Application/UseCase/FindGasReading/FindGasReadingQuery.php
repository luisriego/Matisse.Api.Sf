<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\FindGasReading;

use App\Shared\Application\Query;

final readonly class FindGasReadingQuery implements Query
{
    public function __construct(
        public string $residentUnitId,
        public int $year,
        public int $month,
    ) {}
}
