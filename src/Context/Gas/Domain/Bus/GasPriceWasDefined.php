<?php

declare(strict_types=1);

namespace App\Context\Gas\Domain\Bus;

use App\Shared\Domain\Event\DomainEvent;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class GasPriceWasDefined extends DomainEvent
{
    /**
     * @throws DateMalformedStringException
     */
    public function __construct(
        string $id,
        public float $pricePerM3,
        ?string $eventId = null,
        ?string $occurredOn = null, // Can be a date string or null
    ) {
        parent::__construct(
            $id,
            $eventId ?? Uuid::v4()->toRfc4122(),
            // If $occurredOn is a string, create a DateTimeImmutable from it.
            // If $occurredOn is null, create a new DateTimeImmutable for "now".
            // In both cases, we pass the OBJECT to the parent, not a string.
            $occurredOn ? new DateTimeImmutable($occurredOn) : new DateTimeImmutable(),
        );
    }

    public static function eventName(): string
    {
        return 'gas.price.was.defined';
    }

    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn,
    ): self {
        return new self(
            $aggregateId,
            $body['pricePerM3'],
            $eventId,
            $occurredOn,
        );
    }

    public function toPrimitives(): array
    {
        return [
            'pricePerM3' => $this->pricePerM3,
        ];
    }
}
