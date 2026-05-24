<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Domain\Service;

use App\Context\BillingPolicy\Domain\ResolvedBillingPolicy;
use App\Context\BillingPolicy\Domain\ValueObject\BillingPolicySnapshot;
use DateTimeImmutable;

use function preg_match;

/**
 * Pure resolver: explicit snapshot for target month or inherit from the latest prior month.
 */
final class BillingPolicyResolver
{
    private const TARGET_MONTH_PATTERN = '/^\d{4}-(0[1-9]|1[0-2])$/';

    /**
     * @param array<string, BillingPolicySnapshot> $snapshots keyed by YYYY-MM
     */
    public function resolve(array $snapshots, string $targetMonth): ResolvedBillingPolicy
    {
        if (1 !== preg_match(self::TARGET_MONTH_PATTERN, $targetMonth)) {
            return ResolvedBillingPolicy::empty($targetMonth);
        }

        if (isset($snapshots[$targetMonth])) {
            return $this->fromSnapshot($targetMonth, $targetMonth, true, $snapshots[$targetMonth]);
        }

        $priorMonth = $this->latestPriorMonth(array_keys($snapshots), $targetMonth);
        if ($priorMonth === null) {
            return ResolvedBillingPolicy::empty($targetMonth);
        }

        return $this->fromSnapshot($targetMonth, $priorMonth, false, $snapshots[$priorMonth]);
    }

    /**
     * @param list<string> $months
     */
    private function latestPriorMonth(array $months, string $targetMonth): ?string
    {
        $candidates = [];
        foreach ($months as $month) {
            if (1 !== preg_match(self::TARGET_MONTH_PATTERN, $month)) {
                continue;
            }
            if ($month < $targetMonth) {
                $candidates[] = $month;
            }
        }

        if ($candidates === []) {
            return null;
        }

        sort($candidates);

        return $candidates[\array_key_last($candidates)];
    }

    private function fromSnapshot(
        string $targetMonth,
        string $sourceMonth,
        bool $explicit,
        BillingPolicySnapshot $snapshot,
    ): ResolvedBillingPolicy {
        return new ResolvedBillingPolicy(
            $targetMonth,
            $sourceMonth,
            $explicit,
            $snapshot->extraFeePerUnitCents(),
            $snapshot->reserveFundPerUnitCents(),
            $snapshot->syndicShareTotalCents(),
            $snapshot->gasPricePerM3Cents(),
            $snapshot->recordedAt(),
        );
    }
}
