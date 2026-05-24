<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class PeriodWasClosed extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private readonly int $year,
        private readonly int $month,
        ?string $eventId = null,
        ?DateTimeImmutable $occurredOn = null,
    ) {
        $eventId ??= Uuid::v4()->toRfc4122();
        $occurredOn ??= new DateTimeImmutable();

        parent::__construct($aggregateId, $eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'period.closed';
    }

    public function toPrimitives(): array
    {
        return [
            'year' => $this->year,
            'month' => $this->month,
        ];
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn,
    ): self {
        return new self(
            $aggregateId,
            $body['year'],
            $body['month'],
            $eventId,
            new DateTimeImmutable($occurredOn),
        );
    }

    public function year(): int
    {
        return $this->year;
    }

    public function month(): int
    {
        return $this->month;
    }
}
