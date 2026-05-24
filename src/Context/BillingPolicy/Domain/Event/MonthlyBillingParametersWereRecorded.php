<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Domain\Event;

use App\Context\BillingPolicy\Domain\BillingPolicyAggregateId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Uuid;
use DateMalformedStringException;
use DateTimeImmutable;

final readonly class MonthlyBillingParametersWereRecorded extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private string $targetMonth,
        private int $extraFeePerUnitCents,
        private int $reserveFundPerUnitCents,
        private int $syndicShareTotalCents,
        private ?int $gasPricePerM3Cents,
        private ?string $recordedByUserId = null,
        ?string $eventId = null,
        ?DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct(
            $aggregateId,
            $eventId ?? Uuid::random()->value(),
            $occurredOn ?? new DateTimeImmutable(),
        );
    }

    public static function eventName(): string
    {
        return 'billing_policy.monthly_parameters.were.recorded';
    }

    public static function create(
        string $targetMonth,
        int $extraFeePerUnitCents,
        int $reserveFundPerUnitCents,
        int $syndicShareTotalCents,
        ?int $gasPricePerM3Cents,
        ?string $recordedByUserId = null,
    ): self {
        return new self(
            BillingPolicyAggregateId::VALUE,
            $targetMonth,
            $extraFeePerUnitCents,
            $reserveFundPerUnitCents,
            $syndicShareTotalCents,
            $gasPricePerM3Cents,
            $recordedByUserId,
        );
    }

    public function toPrimitives(): array
    {
        $primitives = [
            'targetMonth' => $this->targetMonth,
            'extraFeePerUnitCents' => $this->extraFeePerUnitCents,
            'reserveFundPerUnitCents' => $this->reserveFundPerUnitCents,
            'syndicShareTotalCents' => $this->syndicShareTotalCents,
            'gasPricePerM3Cents' => $this->gasPricePerM3Cents,
        ];

        if ($this->recordedByUserId !== null && $this->recordedByUserId !== '') {
            $primitives['recordedByUserId'] = $this->recordedByUserId;
        }

        return $primitives;
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
            $body['targetMonth'],
            (int) $body['extraFeePerUnitCents'],
            (int) $body['reserveFundPerUnitCents'],
            (int) $body['syndicShareTotalCents'],
            array_key_exists('gasPricePerM3Cents', $body) && $body['gasPricePerM3Cents'] !== null
                ? (int) $body['gasPricePerM3Cents']
                : null,
            isset($body['recordedByUserId']) && $body['recordedByUserId'] !== ''
                ? (string) $body['recordedByUserId']
                : null,
            $eventId,
            new DateTimeImmutable($occurredOn),
        );
    }

    public function targetMonth(): string
    {
        return $this->targetMonth;
    }
}
