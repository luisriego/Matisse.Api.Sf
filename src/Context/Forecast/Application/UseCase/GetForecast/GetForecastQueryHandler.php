<?php

declare(strict_types=1);

namespace App\Context\Forecast\Application\UseCase\GetForecast;

use App\Context\Forecast\Domain\Service\ForecastBuilder;
use App\Shared\Application\QueryHandler;
use DateMalformedStringException;

final readonly class GetForecastQueryHandler implements QueryHandler
{
    public function __construct(
        private ForecastBuilder $forecastBuilder,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws DateMalformedStringException
     */
    public function __invoke(GetForecastQuery $query): array
    {
        return $this->forecastBuilder->build(
            $query->targetMonth(),
            $query->reconciliationMonth(),
        );
    }
}
