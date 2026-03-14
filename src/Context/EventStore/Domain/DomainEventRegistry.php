<?php

declare(strict_types=1);

namespace App\Context\EventStore\Domain;

interface DomainEventRegistry
{
    public function getClassForEventType(string $eventType): ?string;
}
