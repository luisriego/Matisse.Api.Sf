<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class SlipWasSubmitted extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private readonly string $residentUnitId,
        private readonly int $amount,
        private readonly string $dueDate,
        private readonly int $mainAccountAmount,
        private readonly int $gasAmount,
        private readonly int $reserveFundAmount,
        private readonly int $constructionFundAmount,
        ?string $eventId = null,
        ?DateTimeImmutable $occurredOn = null,
    ) {
        $eventId ??= Uuid::v4()->toRfc4122();
        $occurredOn ??= new DateTimeImmutable();

        parent::__construct($aggregateId, $eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'slip.submitted';
    }

    public function toPrimitives(): array
    {
        return [
            'residentUnitId' => $this->residentUnitId,
            'amount' => $this->amount,
            'dueDate' => $this->dueDate,
            'mainAccountAmount' => $this->mainAccountAmount,
            'gasAmount' => $this->gasAmount,
            'reserveFundAmount' => $this->reserveFundAmount,
            'constructionFundAmount' => $this->constructionFundAmount,
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
            $body['residentUnitId'],
            $body['amount'],
            $body['dueDate'],
            $body['mainAccountAmount'] ?? 0,
            $body['gasAmount'] ?? 0,
            $body['reserveFundAmount'] ?? 0,
            $body['constructionFundAmount'] ?? 0,
            $eventId,
            new DateTimeImmutable($occurredOn),
        );
    }

    public function residentUnitId(): string
    {
        return $this->residentUnitId;
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function dueDate(): string
    {
        return $this->dueDate;
    }

    public function mainAccountAmount(): int
    {
        return $this->mainAccountAmount;
    }

    public function gasAmount(): int
    {
        return $this->gasAmount;
    }

    public function reserveFundAmount(): int
    {
        return $this->reserveFundAmount;
    }

    public function constructionFundAmount(): int
    {
        return $this->constructionFundAmount;
    }
}
