<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain\Bus;

use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\Uid\Uuid as SfUuid;

final readonly class ExpenseWasCompensated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private int $amount,
        private string $accountId,
        private string $dueDate,
        ?string $eventId = null,
        ?string $occurredOn = null
    ) {
        parent::__construct(
            $aggregateId,
            $eventId ?? SfUuid::v4()->toRfc4122(),
            $occurredOn ?? date('Y-m-d H:i:s')
        );
    }

    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn
    ): self {
        return new self(
            $aggregateId,
            $body['amount'],
            $body['accountId'],
            $body['dueDate'],
            $eventId,
            $occurredOn
        );
    }

    public static function eventName(): string
    {
        return 'expense.compensated';
    }

    public function toPrimitives(): array
    {
        return [
            'amount' => $this->amount,
            'accountId' => $this->accountId,
            'dueDate' => $this->dueDate
        ];
    }
}