<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\FindLastGasReading;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Gas\Domain\Exception\GasReadingNotFoundException;
use App\Shared\Application\QueryHandler;
use DateTimeImmutable;

final readonly class FindLastGasReadingQueryHandler implements QueryHandler
{
    public function __construct(private StoredEventRepository $eventRepository)
    {
    }

    public function __invoke(FindLastGasReadingQuery $query): float
    {
        $allEvents = $this->eventRepository->findByEventType('gas.reading.was.recorded');

        $periodEndDate = (new DateTimeImmutable())->modify('first day of -2 month')->modify('last day of this month');

        $validReadings = [];

        foreach ($allEvents as $event) {
            $payload = $event->payload();

            if (!isset($payload['residentUnitId']) || $payload['residentUnitId'] !== $query->residentUnitId) {
                continue;
            }

            $readingPeriodDate = DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%d-%d-01', $payload['year'], $payload['month']));

            if ($readingPeriodDate <= $periodEndDate) {
                $validReadings[] = $event;
            }
        }

        if (empty($validReadings)) {
            throw new GasReadingNotFoundException();
        }

        usort($validReadings, static function (StoredEvent $a, StoredEvent $b) {
            $dateA = sprintf('%d-%02d', $a->payload()['year'], $a->payload()['month']);
            $dateB = sprintf('%d-%02d', $b->payload()['year'], $b->payload()['month']);
            return $dateB <=> $dateA; // Ordenar de más reciente a más antiguo
        });

        $lastValidReading = $validReadings[0];

        return (float) $lastValidReading->payload()['reading'];
    }
}
