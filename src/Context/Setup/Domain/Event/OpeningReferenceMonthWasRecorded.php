<?php

declare(strict_types=1);

namespace App\Context\Setup\Domain\Event;

use App\Context\Setup\Domain\OpeningSetupAggregateId;
use App\Context\Setup\Domain\SyndicAllocationRule;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Uuid;
use DateMalformedStringException;
use DateTimeImmutable;

/**
 * Opening operational baseline: reference expense month and demonstrative / slip parameters.
 * Append-only; latest event is the current baseline for clients and analysis.
 */
final readonly class OpeningReferenceMonthWasRecorded extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private string $referenceMonth,
        private string $syndicAllocationRule,
        private int $extraFeePerUnitCents,
        private int $reserveFundPerUnitCents,
        private ?int $expectedCommonExpensesCents = null,
        private ?int $expectedSyndicShareTotalCents = null,
        private ?int $expectedBoletoTotalCents = null,
        private ?int $optionalGasTotalCents = null,
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
        return 'setup.opening_reference_month.was.recorded';
    }

    public static function create(
        string $referenceMonth,
        SyndicAllocationRule $syndicAllocationRule,
        int $extraFeePerUnitCents,
        int $reserveFundPerUnitCents,
        ?int $expectedCommonExpensesCents = null,
        ?int $expectedSyndicShareTotalCents = null,
        ?int $expectedBoletoTotalCents = null,
        ?int $optionalGasTotalCents = null,
    ): self {
        return new self(
            OpeningSetupAggregateId::VALUE,
            $referenceMonth,
            $syndicAllocationRule->value,
            $extraFeePerUnitCents,
            $reserveFundPerUnitCents,
            $expectedCommonExpensesCents,
            $expectedSyndicShareTotalCents,
            $expectedBoletoTotalCents,
            $optionalGasTotalCents,
        );
    }

    public function toPrimitives(): array
    {
        return [
            'referenceMonth' => $this->referenceMonth,
            'syndicAllocationRule' => $this->syndicAllocationRule,
            'extraFeePerUnitCents' => $this->extraFeePerUnitCents,
            'reserveFundPerUnitCents' => $this->reserveFundPerUnitCents,
            'expectedCommonExpensesCents' => $this->expectedCommonExpensesCents,
            'expectedSyndicShareTotalCents' => $this->expectedSyndicShareTotalCents,
            'expectedBoletoTotalCents' => $this->expectedBoletoTotalCents,
            'optionalGasTotalCents' => $this->optionalGasTotalCents,
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
    ): DomainEvent {
        return new self(
            $aggregateId,
            $body['referenceMonth'],
            $body['syndicAllocationRule'],
            (int) $body['extraFeePerUnitCents'],
            (int) $body['reserveFundPerUnitCents'],
            array_key_exists('expectedCommonExpensesCents', $body) ? (int) $body['expectedCommonExpensesCents'] : null,
            array_key_exists('expectedSyndicShareTotalCents', $body) ? (int) $body['expectedSyndicShareTotalCents'] : null,
            array_key_exists('expectedBoletoTotalCents', $body) ? (int) $body['expectedBoletoTotalCents'] : null,
            array_key_exists('optionalGasTotalCents', $body) ? (int) $body['optionalGasTotalCents'] : null,
            $eventId,
            new DateTimeImmutable($occurredOn),
        );
    }

    public function referenceMonth(): string
    {
        return $this->referenceMonth;
    }

    public function syndicAllocationRule(): SyndicAllocationRule
    {
        return SyndicAllocationRule::from($this->syndicAllocationRule);
    }
}
