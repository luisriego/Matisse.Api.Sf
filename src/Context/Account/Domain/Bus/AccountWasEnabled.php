<?php

declare(strict_types=1);

namespace App\Context\Account\Domain\Bus;

use App\Shared\Domain\Event\DomainEvent;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid as SfUuid;

final readonly class AccountWasEnabled extends DomainEvent
{
    public function __construct(
        string $id,
        ?string $eventId = null,
        ?DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct(
            $id,
            $eventId ?? SfUuid::v4()->toRfc4122(),
            $occurredOn ?? new DateTimeImmutable(),
        );
    }

    public static function eventName(): string
    {
        return 'account.enabled';
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
