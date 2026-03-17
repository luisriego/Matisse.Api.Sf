<?php

declare(strict_types=1);

namespace App\Context\Income\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class IncomeDueDateWasChanged extends DomainEvent
{
    /**
     * @throws DateMalformedStringException
     */
    public function __construct(
        string $id,
        public string $newDueDate,
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
        return 'income.due_date.was.changed';
    }

    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn,
    ): self {
        return new self(
            $aggregateId,
            $body['newDueDate'],
            $eventId,
            $occurredOn,
        );
    }

    public function toPrimitives(): array
    {
        return [
            'newDueDate' => $this->newDueDate,
        ];
    }
}
