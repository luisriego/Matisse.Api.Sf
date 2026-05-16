<?php

declare(strict_types=1);

namespace App\Context\Setup\Application\UseCase\RecordOpeningReferenceMonth;

use App\Shared\Application\Command;

final readonly class RecordOpeningReferenceMonthCommand implements Command
{
    public function __construct(
        private string $referenceMonth,
        private string $syndicAllocationRule,
        private int $extraFeePerUnitCents,
        private int $reserveFundPerUnitCents,
        private ?int $expectedCommonExpensesCents,
        private ?int $expectedSyndicShareTotalCents,
        private ?int $expectedBoletoTotalCents,
        private ?int $optionalGasTotalCents,
        private ?string $ledgerAccountId,
    ) {}

    public function referenceMonth(): string
    {
        return $this->referenceMonth;
    }

    public function syndicAllocationRule(): string
    {
        return $this->syndicAllocationRule;
    }

    public function extraFeePerUnitCents(): int
    {
        return $this->extraFeePerUnitCents;
    }

    public function reserveFundPerUnitCents(): int
    {
        return $this->reserveFundPerUnitCents;
    }

    public function expectedCommonExpensesCents(): ?int
    {
        return $this->expectedCommonExpensesCents;
    }

    public function expectedSyndicShareTotalCents(): ?int
    {
        return $this->expectedSyndicShareTotalCents;
    }

    public function expectedBoletoTotalCents(): ?int
    {
        return $this->expectedBoletoTotalCents;
    }

    public function optionalGasTotalCents(): ?int
    {
        return $this->optionalGasTotalCents;
    }

    public function ledgerAccountId(): ?string
    {
        return $this->ledgerAccountId;
    }
}
