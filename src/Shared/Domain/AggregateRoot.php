<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use App\Shared\Domain\Event\DomainEvent;

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

    final protected function record(DomainEvent $domainEvent): void
    {
        $this->domainEvents[] = $domainEvent;
    }
}
