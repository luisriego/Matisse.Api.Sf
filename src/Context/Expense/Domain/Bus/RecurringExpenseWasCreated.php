<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain\Bus;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Uuid;
use DateMalformedStringException;
use DateTimeImmutable;

final readonly class RecurringExpenseWasCreated extends DomainEvent
{
    public function __construct(
        string $id,
        private ?string $accountId, // Made nullable
        private int $amount,
        private string $typeId,
        private int $dueDay,
        private array $monthsOfYear,
        private string $startDate,
        private ?string $endDate,
        private ?string $description,
        private ?string $notes,
        ?string $eventId = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $eventId ?? Uuid::random()->value(), $occurredOn ?? new DateTimeImmutable());
    }

    public static function eventName(): string
    {
        return 'expense.recurring.created';
    }

    public function toPrimitives(): array
    {
        return [
            'accountId' => $this->accountId,
            'amount' => $this->amount,
            'typeId' => $this->typeId,
            'dueDay' => $this->dueDay,
            'monthsOfYear' => $this->monthsOfYear,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'description' => $this->description,
            'notes' => $this->notes,
        ];
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn
    ): DomainEvent {
        return new self(
            $aggregateId,
            $body['accountId'] ?? null, // Handle nullable
            $body['amount'],
            $body['typeId'],
            $body['dueDay'],
            $body['monthsOfYear'],
            $body['startDate'],
            $body['endDate'],
            $body['description'],
            $body['notes'],
            $eventId,
            new DateTimeImmutable($occurredOn)
        );
    }
}
