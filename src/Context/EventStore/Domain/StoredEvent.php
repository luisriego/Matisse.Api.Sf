<?php

declare(strict_types=1);

namespace App\Context\EventStore\Domain;

use App\Shared\Domain\Uuid;
use DateTimeImmutable;

final class StoredEvent
{
    private Uuid $id;
    private string $aggregateId;
    private string $eventType;
    private array $payload;
    private DateTimeImmutable $occurredAt;

    private function __construct(
        Uuid $id,
        string $aggregateId,
        string $eventType,
        array $payload,
        DateTimeImmutable $occurredAt,
    ) {
        $this->id = $id;
        $this->aggregateId = $aggregateId;
        $this->eventType = $eventType;
        $this->payload = $payload;
        $this->occurredAt = $occurredAt;
    }

    public static function create(
        string $aggregateId,
        string $eventType,
        array $payload,
    ): self {
        return new self(
            Uuid::random(),
            $aggregateId,
            $eventType,
            $payload,
            new DateTimeImmutable(),
        );
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
