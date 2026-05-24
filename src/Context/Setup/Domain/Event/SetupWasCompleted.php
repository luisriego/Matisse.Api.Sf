<?php

declare(strict_types=1);

namespace App\Context\Setup\Domain\Event;

use App\Context\Setup\Domain\OpeningSetupAggregateId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Uuid;
use DateMalformedStringException;
use DateTimeImmutable;

/**
 * One-time (per DB) milestone: condominium setup is finished; API must not enforce SETUP_REQUIRED again.
 */
final readonly class SetupWasCompleted extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        ?string $eventId = null,
        ?DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct(
            $aggregateId,
            $eventId ?? Uuid::random()->value(),
            $occurredOn ?? new DateTimeImmutable(),
        );
    }

    public static function createForCondominium(): self
    {
        return new self(OpeningSetupAggregateId::VALUE);
    }

    public static function eventName(): string
    {
        return 'setup.was.completed';
    }

    public function toPrimitives(): array
    {
        return [];
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn,
    ): DomainEvent {
        return new self($aggregateId, $eventId, new DateTimeImmutable($occurredOn));
    }
}
