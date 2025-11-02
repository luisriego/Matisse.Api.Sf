<?php

declare(strict_types=1);

namespace App\Context\EventStore\Domain;

use App\Shared\Domain\Event\DomainEvent;
use DateTimeImmutable;

interface StoredEventRepository
{
    public function save(StoredEvent $event, bool $flush = true): void;

    public function findByAggregateId(string $aggregateId): array;

    public function findByEventType(string $eventType): array;

    public function findByEventTypesAndOccurredBetween(array $eventTypes, DateTimeImmutable $startDate, ?DateTimeImmutable $endDate): array;

    public function findByEventTypesAndOccurredBetweenAndAggregateId(
        array $eventTypes,
        DateTimeImmutable $startDate,
        ?DateTimeImmutable $endDate,
        string $aggregateId,
    ): array;

    public function append(DomainEvent $event): void;
}
