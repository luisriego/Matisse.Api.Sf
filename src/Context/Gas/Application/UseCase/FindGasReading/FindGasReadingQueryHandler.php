<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\FindGasReading;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Gas\Domain\Exception\GasReadingNotFoundException;
use App\Shared\Application\QueryHandler;

use function usort;

final readonly class FindGasReadingQueryHandler implements QueryHandler
{
    public function __construct(private StoredEventRepository $eventRepository) {}

    public function __invoke(FindGasReadingQuery $query): float
    {
        $allEvents = $this->eventRepository->findByEventType('gas.reading.was.recorded');

        $validReadings = [];

        foreach ($allEvents as $event) {
            $payload = $event->payload();

            if (
                !isset($payload['residentUnitId']) || $payload['residentUnitId'] !== $query->residentUnitId->value()
                || !isset($payload['year']) || $payload['year'] !== $query->year->value()
                || !isset($payload['month']) || $payload['month'] !== $query->month->value()
            ) {
                continue;
            }

            $validReadings[] = $event;
        }

        if (empty($validReadings)) {
            throw new GasReadingNotFoundException();
        }

        usort($validReadings, static function (StoredEvent $a, StoredEvent $b) {
            return $b->occurredAt() <=> $a->occurredAt();
        });

        $lastValidReading = $validReadings[0];

        return (float) $lastValidReading->payload()['reading'];
    }
}
