<?php

declare(strict_types=1);

namespace App\Context\Forecast\Application\UseCase\GetForecast;

use App\Shared\Application\Query;

final readonly class GetForecastQuery implements Query
{
    public function __construct(
        private string $targetMonth,
        private ?string $reconciliationMonth = null,
    ) {}

    public function targetMonth(): string
    {
        return $this->targetMonth;
    }

    public function reconciliationMonth(): ?string
    {
        return $this->reconciliationMonth;
    }
}
