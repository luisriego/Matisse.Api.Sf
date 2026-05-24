<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Domain;

use DateTimeImmutable;

class BillingPolicyMonthSnapshot
{
    private string $targetMonth;

    private int $extraFeePerUnitCents;

    private int $reserveFundPerUnitCents;

    private int $syndicShareTotalCents;

    private ?int $gasPricePerM3Cents;

    private DateTimeImmutable $recordedAt;

    public function __construct(
        string $targetMonth,
        int $extraFeePerUnitCents,
        int $reserveFundPerUnitCents,
        int $syndicShareTotalCents,
        ?int $gasPricePerM3Cents,
        DateTimeImmutable $recordedAt,
    ) {
        $this->targetMonth = $targetMonth;
        $this->extraFeePerUnitCents = $extraFeePerUnitCents;
        $this->reserveFundPerUnitCents = $reserveFundPerUnitCents;
        $this->syndicShareTotalCents = $syndicShareTotalCents;
        $this->gasPricePerM3Cents = $gasPricePerM3Cents;
        $this->recordedAt = $recordedAt;
    }

    public function targetMonth(): string
    {
        return $this->targetMonth;
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

    public function gasPricePerM3Cents(): ?int
    {
        return $this->gasPricePerM3Cents;
    }

    public function recordedAt(): DateTimeImmutable
    {
        return $this->recordedAt;
    }

    public function update(
        int $extraFeePerUnitCents,
        int $reserveFundPerUnitCents,
        int $syndicShareTotalCents,
        ?int $gasPricePerM3Cents,
        DateTimeImmutable $recordedAt,
    ): void {
        $this->extraFeePerUnitCents = $extraFeePerUnitCents;
        $this->reserveFundPerUnitCents = $reserveFundPerUnitCents;
        $this->syndicShareTotalCents = $syndicShareTotalCents;
        $this->gasPricePerM3Cents = $gasPricePerM3Cents;
        $this->recordedAt = $recordedAt;
    }
}
