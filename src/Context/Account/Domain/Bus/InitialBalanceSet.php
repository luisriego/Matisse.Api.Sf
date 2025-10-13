<?php

declare(strict_types=1);

namespace App\Context\Account\Domain\Bus;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Uuid;
use DateTimeImmutable;

final readonly class InitialBalanceSet extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private int $amount,
        private string $date,
        ?string $eventId = null,
        ?DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($aggregateId, $eventId ?? Uuid::random()->value(), $occurredOn ?? new DateTimeImmutable());
    }

    public static function eventName(): string
    {
        return 'account.initial_balance.set';
    }

    public function toPrimitives(): array
    {
        return [
            'amount' => $this->amount,
            'date' => $this->date,
        ];
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
            $body['date'],
            $eventId,
            new DateTimeImmutable($occurredOn),
        );
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function date(): string
    {
        return $this->date;
    }
}
