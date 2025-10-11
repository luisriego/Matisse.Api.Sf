<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain\Bus;

use App\Shared\Domain\Event\DomainEvent;
use DateMalformedStringException;
use DateTimeImmutable;

final readonly class ExpenseWasActivated extends DomainEvent
{
    public function __construct(
        string $id,
        ?string $eventId = null,
        ?DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'expense.activated';
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
        return new self(
            $aggregateId,
            $eventId,
            new DateTimeImmutable($occurredOn),
        );
    }
}
