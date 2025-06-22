<?php

declare(strict_types=1);

namespace App\Context\EventStore\Domain;

interface StoredEventRepository
{
    public function save(StoredEvent $event): void;

    public function findByAggregateId(string $aggregateId): StoredEvent;
}
