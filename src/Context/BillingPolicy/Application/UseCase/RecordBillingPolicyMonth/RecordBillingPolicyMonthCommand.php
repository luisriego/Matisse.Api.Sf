<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Application\UseCase\RecordBillingPolicyMonth;

use App\Shared\Application\Command;

final readonly class RecordBillingPolicyMonthCommand implements Command
{
    public function __construct(
        private string $targetMonth,
        private int $extraFeePerUnitCents,
        private int $reserveFundPerUnitCents,
        private int $syndicShareTotalCents,
        private ?int $gasPricePerM3Cents,
        private ?string $recordedByUserId = null,
    ) {}

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

    public function recordedByUserId(): ?string
    {
        return $this->recordedByUserId;
    }
}
