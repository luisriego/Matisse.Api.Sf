<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain\Bus;

use App\Shared\Domain\Event\DomainEvent;
use DateTimeImmutable;
use Exception;
use Symfony\Component\Uid\Uuid as SfUuid;

final readonly class ExpenseWasCompensated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private int $amount,
        private string $type,
        private string $accountId,
        private string $dueDate,
        private ?string $residentUnitId = null, // Added parameter
        ?string $eventId = null,
        ?DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct(
            $aggregateId,
            $eventId ?? SfUuid::v4()->toRfc4122(),
            $occurredOn ?? new DateTimeImmutable(),
        );
    }

    /**
     * @throws Exception
     */
    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn,
    ): self {
        return new self(
            $aggregateId,
            $body['amount'],
            $body['type'],
            $body['accountId'],
            $body['dueDate'],
            $body['residentUnitId'] ?? null, // Added parameter
            $eventId,
            new DateTimeImmutable($occurredOn),
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
            'type' => $this->type,
            'accountId' => $this->accountId,
            'dueDate' => $this->dueDate,
            'residentUnitId' => $this->residentUnitId, // Added to primitives
        ];
    }
}
