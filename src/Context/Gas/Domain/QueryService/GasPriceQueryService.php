<?php

declare(strict_types=1);

namespace App\Context\Gas\Domain\QueryService;

use App\Shared\Domain\ValueObject\Month;
use App\Shared\Domain\ValueObject\Year;

interface GasPriceQueryService
{
    public function getPricePerM3(
        Year $year,
        Month $month
    ): ?float;
}
