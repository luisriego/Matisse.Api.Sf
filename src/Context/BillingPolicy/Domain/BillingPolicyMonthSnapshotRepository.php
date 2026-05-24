<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Domain;

interface BillingPolicyMonthSnapshotRepository
{
    /**
     * @return array<string, BillingPolicyMonthSnapshot> keyed by targetMonth (YYYY-MM)
     */
    public function findAllIndexedByTargetMonth(): array;

    public function upsert(
        string $targetMonth,
        int $extraFeePerUnitCents,
        int $reserveFundPerUnitCents,
        int $syndicShareTotalCents,
        ?int $gasPricePerM3Cents,
        \DateTimeImmutable $recordedAt,
    ): void;

    public function flush(): void;
}
