<?php

declare(strict_types=1);

namespace App\Tests\Context\Shared\Infrastructure\Persistence;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Shared\Domain\Event\DomainEvent;
use DateTimeImmutable;

class InMemoryStoredEventRepository implements StoredEventRepository
{
    /**
     * @var StoredEvent[]
     */
    private array $events = [];

    public function save(StoredEvent $event, bool $flush = true): void
    {
        $this->events[] = $event;
        usort($this->events, fn(StoredEvent $a, StoredEvent $b) => $a->occurredAt() <=> $b->occurredAt());
    }

    public function findByAggregateId(string $aggregateId): array
    {
        return array_values(array_filter($this->events, fn(StoredEvent $event) => $event->aggregateId() === $aggregateId));
    }

    public function findByEventNamesAndOccurredBetween(
        array $eventNames,
        DateTimeImmutable $startDate,
        ?DateTimeImmutable $endDate
    ): array {
        return array_values(array_filter(
            $this->events,
            function (StoredEvent $event) use ($eventNames, $startDate, $endDate) {
                $isEventNameMatch = in_array($event->eventType(), $eventNames);
                $isAfterStartDate = $event->occurredAt() >= $startDate;
                $isBeforeEndDate = ($endDate === null) || ($event->occurredAt() <= $endDate);

                return $isEventNameMatch && $isAfterStartDate && $isBeforeEndDate;
            }
        ));
    }

    public function findByEventNamesAndOccurredBetweenAndAggregateId(
        array $eventNames,
        DateTimeImmutable $startDate,
        ?DateTimeImmutable $endDate,
        string $aggregateId
    ): array {
        return array_values(array_filter(
            $this->events,
            function (StoredEvent $event) use ($eventNames, $startDate, $endDate, $aggregateId) {
                $isEventNameMatch = in_array($event->eventType(), $eventNames);
                $isAfterStartDate = $event->occurredAt() >= $startDate;
                $isBeforeEndDate = ($endDate === null) || ($event->occurredAt() <= $endDate);
                $isAggregateIdMatch = $event->aggregateId() === $aggregateId;

                return $isEventNameMatch && $isAfterStartDate && $isBeforeEndDate && $isAggregateIdMatch;
            }
        ));
    }

    public function append(DomainEvent $event): void
    {
        $this->save(StoredEvent::create(
            $event->aggregateId(),
            $event::eventName(),
            $event->toPrimitives(),
            $event->occurredOn()
        ));
    }

    public function clear(): void
    {
        $this->events = [];
    }
}
