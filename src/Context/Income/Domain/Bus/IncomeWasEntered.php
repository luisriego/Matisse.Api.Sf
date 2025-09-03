<?php

declare(strict_types=1);

namespace App\Context\Income\Domain\Bus;

use App\Shared\Domain\Event\DomainEvent;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid as SfUuid;

final readonly class IncomeWasEntered extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private int $amount,
        private string $residentUnitId,
        private string $type,
        private string $dueDate,
        ?string $eventId = null,
        ?DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct(
            $aggregateId,
            $eventId ?? SfUuid::v4()->toRfc4122(),
            $occurredOn ?? new DateTimeImmutable(),
        );
    }

    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn,
    ): DomainEvent {
        return new self(
            $aggregateId,
            $body['amount'],
            $body['residentUnitId'],
            $body['type'],
            $body['dueDate'],
            $eventId,
            new DateTimeImmutable($occurredOn),
        );
    }

    public static function eventName(): string
    {
        return 'income.entered';
    }

    public function toPrimitives(): array
    {
        return [
            'amount' => $this->amount,
            'residentUnitId' => $this->residentUnitId,
            'type' => $this->type,
            'dueDate' => $this->dueDate,
        ];
    }
}
