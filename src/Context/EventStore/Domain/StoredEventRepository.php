<?php

namespace App\Context\EventStore\Domain;

interface StoredEventRepository
{
    public function save(StoredEvent $event): void;
    public function findByAggregateId(string $aggregateId): StoredEvent;

}