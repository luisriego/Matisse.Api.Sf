<?php

declare(strict_types=1);

namespace App\Context\Account\Domain\Bus;

use App\Shared\Domain\Event\DomainEvent;
use DateTimeImmutable;

final class ExpenseAddedToAccount extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private readonly string $expenseId,
        string $eventId = null,
        DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($aggregateId, $eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'expense.added_to_account';
    }

    public function toPrimitives(): array
    {
        return [
            'aggregateId' => $this->aggregateId(),
            'expenseId' => $this->expenseId,
        ];
    }

    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        DateTimeImmutable $occurredOn
    ): self {
        return new self(
            $aggregateId,
            $body['expenseId'],
            $eventId,
            $occurredOn
        );
    }

    public function expenseId(): string
    {
        return $this->expenseId;
    }
}
