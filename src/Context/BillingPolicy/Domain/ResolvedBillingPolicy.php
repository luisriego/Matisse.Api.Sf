<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Domain;

use DateTimeImmutable;

use const DATE_ATOM;

final readonly class ResolvedBillingPolicy
{
    public const SYNDIC_ALLOCATION_RULE = 'equal_parts';

    public function __construct(
        private string $targetMonth,
        private ?string $sourceMonth,
        private bool $explicit,
        private int $extraFeePerUnitCents,
        private int $reserveFundPerUnitCents,
        private int $syndicShareTotalCents,
        private ?int $gasPricePerM3Cents,
        private ?DateTimeImmutable $recordedAt,
    ) {}

    public static function empty(string $targetMonth): self
    {
        return new self(
            $targetMonth,
            null,
            false,
            0,
            0,
            60_000,
            null,
            null,
        );
    }

    public function targetMonth(): string
    {
        return $this->targetMonth;
    }

    public function sourceMonth(): ?string
    {
        return $this->sourceMonth;
    }

    public function explicit(): bool
    {
        return $this->explicit;
    }

    public function extraFeePerUnitCents(): int
    {
        return $this->extraFeePerUnitCents;
    }

    public function reserveFundPerUnitCents(): int
    {
        return $this->reserveFundPerUnitCents;
    }

    public function syndicShareTotalCents(): int
    {
        return $this->syndicShareTotalCents;
    }

    public function syndicAllocationRule(): string
    {
        return self::SYNDIC_ALLOCATION_RULE;
    }

    public function gasPricePerM3Cents(): ?int
    {
        return $this->gasPricePerM3Cents;
    }

    public function recordedAt(): ?DateTimeImmutable
    {
        return $this->recordedAt;
    }

    public function hasPolicy(): bool
    {
        return $this->sourceMonth !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'targetMonth' => $this->targetMonth,
            'sourceMonth' => $this->sourceMonth,
            'explicit' => $this->explicit,
            'extraFeePerUnitCents' => $this->extraFeePerUnitCents,
            'reserveFundPerUnitCents' => $this->reserveFundPerUnitCents,
            'syndicShareTotalCents' => $this->syndicShareTotalCents,
            'syndicAllocationRule' => self::SYNDIC_ALLOCATION_RULE,
            'gasPricePerM3Cents' => $this->gasPricePerM3Cents,
            'recordedAt' => $this->recordedAt?->format(DATE_ATOM),
        ];
    }
}
