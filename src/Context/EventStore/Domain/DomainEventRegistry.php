<?php

declare(strict_types=1);

namespace App\Context\EventStore\Domain;

interface DomainEventRegistry
{
    /**
     * Returns the FQCN of the DomainEvent class for the given event type name,
     * or null if the type is not registered.
     *
     * @return class-string|null
     */
    public function getClassForEventType(string $eventType): ?string;
}
