<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\EventBus;

abstract class AggregateRoot
{
    private array $domainEvents = [];

    final public function pullDomainEvents(): array
    {
        $recordedDomainEvents = $this->domainEvents;
        $this->domainEvents = [];

        return $recordedDomainEvents;
    }

    final public function hasDomainEvents(): bool
    {
        return !empty($this->domainEvents);
    }

    final public function publishDomainEvents(EventBus $bus): void
    {
        if ($this->hasDomainEvents()) {
            $bus->publish(...$this->pullDomainEvents());
        }
    }

    final protected function record(DomainEvent $domainEvent): void
    {
        $this->domainEvents[] = $domainEvent;
    }
}
