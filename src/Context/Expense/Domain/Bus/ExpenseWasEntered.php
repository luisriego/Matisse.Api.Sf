<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain\Bus;

use App\Shared\Domain\Event\DomainEvent;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid as SfUuid;

use function date;

final readonly class ExpenseWasEntered extends DomainEvent
{
    /**
     * @throws \DateMalformedStringException
     */
    public function __construct(
        string $aggregateId,
        private int $amount,
        private string $type,
        private string $accountId,
        private string $dueDate,
        ?string $eventId = null,
        ?string $occurredOn = null,
    ) {
        $occurredOnObject = null;
        if ($occurredOn === null) {
            $occurredOnObject = new DateTimeImmutable();
        } else {
            $occurredOnObject = new DateTimeImmutable($occurredOn);
        }

        parent::__construct(
            $aggregateId,
            $eventId ?? SfUuid::v4()->toRfc4122(),
            $occurredOnObject,
        );
    }

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
            $eventId,
            $occurredOn,
        );
    }

    public static function eventName(): string
    {
        return 'expense.entered';
    }

    public function toPrimitives(): array
    {
        return [
            'amount' => $this->amount,
            'type' => $this->type,
            'accountId' => $this->accountId,
            'dueDate' => $this->dueDate,
        ];
    }
}
