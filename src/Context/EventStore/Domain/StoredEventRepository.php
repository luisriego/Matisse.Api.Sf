<?php

declare(strict_types=1);

namespace App\Context\EventStore\Domain;

interface StoredEventRepository
{
    public function save(StoredEvent $event, bool $flush = true): void;

    public function findByAggregateId(string $aggregateId): StoredEvent;
}
