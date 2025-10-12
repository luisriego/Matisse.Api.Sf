<?php

declare(strict_types=1);

namespace App\Context\EventStore\Domain;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Uuid;
use DateTimeImmutable;
use App\Context\Income\Domain\Bus\IncomeWasEntered;
use App\Context\Expense\Domain\Bus\ExpenseWasEntered;
use App\Context\Account\Domain\Bus\InitialBalanceSet; // Importar el nuevo evento

class StoredEvent
{
    private string $id;
    private string $aggregateId;
    private string $eventType;
    private array $payload;
    private DateTimeImmutable $occurredAt;

    private function __construct(
        string $id,
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
        ?DateTimeImmutable $occurredAt = null
    ): self {
        return new self(
            Uuid::random()->value(),
            $aggregateId,
            $eventType,
            $payload,
            $occurredAt ?? new DateTimeImmutable(),
        );
    }

    public function id(): string
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

    public function toDomainEvent(): DomainEvent
    {
        $eventClassMap = [
            IncomeWasEntered::eventName() => IncomeWasEntered::class,
            ExpenseWasEntered::eventName() => ExpenseWasEntered::class,
            InitialBalanceSet::eventName() => InitialBalanceSet::class, // Añadir el mapeo para InitialBalanceSet
        ];

        $eventClassName = $eventClassMap[$this->eventType()] ?? null;

        if ($eventClassName === null || !class_exists($eventClassName)) {
            throw new \RuntimeException(sprintf('Event class for type "%s" not found or not mapped.', $this->eventType()));
        }

        /** @var DomainEvent $eventClass */
        $eventClass = $eventClassName;

        return $eventClass::fromPrimitives(
            $this->aggregateId(),
            $this->payload(),
            $this->id(),
            $this->occurredAt()->format(DATE_ATOM)
        );
    }
}
