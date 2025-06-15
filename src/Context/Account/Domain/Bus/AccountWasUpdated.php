<?php

declare(strict_types=1);

namespace App\Context\Account\Domain\Bus;

use App\Shared\Domain\DomainEvent;

final readonly class AccountWasUpdated extends DomainEvent
{
    public function __construct(
        string $id,
        ?string $eventId = '',
        ?string $occurredOn = '',
    ) {
        parent::__construct($id, $eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'account.updated';
    }

    public function toPrimitives(): array
    {
        return [];
    }

    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn,
    ): DomainEvent {
        return new self($aggregateId, $eventId, $occurredOn);
    }
}
