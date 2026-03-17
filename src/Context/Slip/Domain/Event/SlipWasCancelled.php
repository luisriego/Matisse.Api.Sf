<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class SlipWasCancelled extends DomainEvent
{
    /**
     * @throws DateMalformedStringException
     */
    public function __construct(
        string $id,
        ?string $eventId = null,
        ?string $occurredOn = null,
    ) {
        parent::__construct(
            $id,
            $eventId ?? Uuid::v4()->toRfc4122(),
            $occurredOn ? new DateTimeImmutable($occurredOn) : new DateTimeImmutable(),
        );
    }

    public static function eventName(): string
    {
        return 'slip.was.cancelled';
    }

    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn,
    ): self {
        return new self(
            $aggregateId,
            $eventId,
            $occurredOn,
        );
    }

    public function toPrimitives(): array
    {
        return [];
    }
}
