<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use DateTimeImmutable;

abstract readonly class DomainEvent implements DomainEventInterface
{
    public function __construct(
        private readonly string $aggregateId,
        private readonly string $eventId,
        private readonly DateTimeImmutable $occurredOn,
    ) {}

    abstract public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn,
    ): self;

    abstract public static function eventName(): string;

    abstract public function toPrimitives(): array;

    final public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    final public function eventId(): string
    {
        return $this->eventId;
    }

    final public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
