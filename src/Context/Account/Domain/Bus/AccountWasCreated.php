<?php

declare(strict_types=1);

namespace App\Context\Account\Domain\Bus;

use App\Shared\Domain\Event\DomainEvent;

final readonly class AccountWasCreated extends DomainEvent
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
        return 'account.created';
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
