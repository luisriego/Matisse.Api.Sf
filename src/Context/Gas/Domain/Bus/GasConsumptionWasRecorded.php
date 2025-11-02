<?php

declare(strict_types=1);

namespace App\Context\Gas\Domain\Bus;

use App\Shared\Domain\Event\DomainEvent;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class GasConsumptionWasRecorded extends DomainEvent
{
    /**
     * @throws DateMalformedStringException
     */
    public function __construct(
        string $id,
        public string $residentUnitId,
        public int $year,
        public int $month,
        public float $consumption,
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
        return 'gas.consumption.was.recorded';
    }

    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn,
    ): self {
        return new self(
            $aggregateId,
            $body['residentUnitId'],
            $body['year'],
            $body['month'],
            $body['consumption'],
            $eventId,
            $occurredOn,
        );
    }

    public function toPrimitives(): array
    {
        return [
            'residentUnitId' => $this->residentUnitId,
            'year' => $this->year,
            'month' => $this->month,
            'consumption' => $this->consumption,
        ];
    }
}