<?php

declare(strict_types=1);

namespace App\Context\Gas\Domain\QueryService;

use App\Context\Gas\Domain\ValueObject\ReadingInM3;
use App\Context\ResidentUnit\Domain\ResidentUnitId;
use App\Shared\Domain\ValueObject\Month;
use App\Shared\Domain\ValueObject\Year;

interface GasReadingQueryService
{
    public function getPreviousMonthReading(
        ResidentUnitId $residentUnitId,
        Year $year,
        Month $month,
    ): ?ReadingInM3;
}
