<?php

declare(strict_types=1);

namespace App\Context\Income\Domain\Bus;

use App\Shared\Domain\Event\DomainEvent;
use DateMalformedStringException;
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
        private ?string $description,
        private readonly int $mainAccountAmount,
        private readonly int $gasAmount,
        private readonly int $reserveFundAmount,
        private readonly int $constructionFundAmount,
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
     * @throws DateMalformedStringException
     */
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
            $body['description'] ?? null,
            $body['mainAccountAmount'] ?? 0,
            $body['gasAmount'] ?? 0,
            $body['reserveFundAmount'] ?? 0,
            $body['constructionFundAmount'] ?? 0,
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
            'description' => $this->description,
            'mainAccountAmount' => $this->mainAccountAmount,
            'gasAmount' => $this->gasAmount,
            'reserveFundAmount' => $this->reserveFundAmount,
            'constructionFundAmount' => $this->constructionFundAmount,
        ];
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
