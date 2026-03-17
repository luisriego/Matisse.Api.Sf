<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class ResidentUnitRecipientWasAppended extends DomainEvent
{
    /**
     * @throws DateMalformedStringException
     */
    public function __construct(
        string $id,
        public string $name,
        public string $email,
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
        return 'resident_unit.recipient.was.appended';
    }

    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn,
    ): self {
        return new self(
            $aggregateId,
            $body['name'],
            $body['email'],
            $eventId,
            $occurredOn,
        );
    }

    public function toPrimitives(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
