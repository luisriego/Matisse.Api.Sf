<?php

declare(strict_types=1);

namespace App\Context\Condominium\Domain\Bus;

use App\Shared\Domain\Event\DomainEvent;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid as SfUuid;

final readonly class ConstructionFundAmountSet extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private int $amount,
        private string $effectiveDate,
        private ?string $userId = null,
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
    ): self {
        return new self(
            $aggregateId,
            $body['amount'],
            $body['effectiveDate'],
            $body['userId'] ?? null,
            $eventId,
            new DateTimeImmutable($occurredOn),
        );
    }

    public static function eventName(): string
    {
        return 'condominium.construction_fund_amount_set';
    }

    public function toPrimitives(): array
    {
        return [
            'amount' => $this->amount,
            'effectiveDate' => $this->effectiveDate,
            'userId' => $this->userId,
        ];
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function effectiveDate(): string
    {
        return $this->effectiveDate;
    }

    public function userId(): ?string
    {
        return $this->userId;
    }
}
