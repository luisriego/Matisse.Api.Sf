<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\GetAverageGasConsumption;

use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Gas\Domain\Exception\NotEnoughReadingsException;
use App\Shared\Application\QueryHandler;
use DateMalformedStringException;
use DateTime;

use function array_sum;
use function array_values;
use function count;
use function ksort;
use function round;

final readonly class GetAverageGasConsumptionQueryHandler implements QueryHandler
{
    public function __construct(private StoredEventRepository $storedEventRepository) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(GetAverageGasConsumptionQuery $query): float
    {
        $events = $this->storedEventRepository->findByEventType('gas.reading.was.recorded');

        $readingsByUnit = [];

        foreach ($events as $event) {
            $payload = $event->payload();

            if ($payload['residentUnitId'] === $query->residentUnitId) {
                $date = new DateTime($payload['year'] . '-' . $payload['month'] . '-01');
                $readingsByUnit[$date->getTimestamp()] = $payload['reading'];
            }
        }

        // Sort readings by date to calculate consumption correctly
        ksort($readingsByUnit);

        if (count($readingsByUnit) < 2) {
            throw new NotEnoughReadingsException('Not enough readings to calculate average consumption.');
        }

        $consumptions = [];
        $readings = array_values($readingsByUnit);

        for ($i = 1; $i < count($readings); $i++) {
            $consumption = $readings[$i] - $readings[$i - 1];

            if ($consumption >= 0) { // Ignore negative consumption (e.g., meter reset)
                $consumptions[] = $consumption;
            }
        }

        if (empty($consumptions)) {
            throw new NotEnoughReadingsException('No valid consumption periods found.');
        }

        $average = array_sum($consumptions) / count($consumptions);

        return round($average, 3); // Round to 3 decimal places
    }
}
