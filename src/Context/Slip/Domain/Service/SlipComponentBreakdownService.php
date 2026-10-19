<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Service;

use App\Context\ResidentUnit\Domain\ResidentUnit;

use function array_fill;
use function count;
use function floor;

readonly class SlipComponentBreakdownService
{
    /**
     * @param array<int, ResidentUnit> $residentUnits
     * @param array<string, int>       $individualByUnit
     * @param array<string, int>       $gasByUnit
     *
     * @return array{
     *     units: list<array<string, int|float|string>>,
     *     components: array<string, int>,
     *     totals: array<string, int>
     * }
     */
    public function build(
        array $residentUnits,
        int $baseEqualPoolCents,
        int $syndicEqualPoolCents,
        int $fractionPoolCents,
        array $individualByUnit,
        array $gasByUnit,
        int $extraPerUnitCents,
        int $reservePerUnitCents,
    ): array {
        if ($residentUnits === []) {
            return [
                'units' => [],
                'components' => [
                    'baseTotalCents' => 0,
                    'syndicTotalCents' => 0,
                    'extraTotalCents' => 0,
                    'reserveTotalCents' => 0,
                    'gasTotalCents' => 0,
                    'grandTotalCents' => 0,
                ],
                'totals' => [
                    'totalCents' => 0,
                    'componentsTotalCents' => 0,
                    'differenceCents' => 0,
                ],
            ];
        }

        $baseEqualByUnit = $this->allocateEqualPool($residentUnits, $baseEqualPoolCents);
        $syndicByUnit = $this->allocateEqualPool($residentUnits, $syndicEqualPoolCents);
        $fractionByUnit = $this->allocateFractionPool($residentUnits, $fractionPoolCents);

        $units = [];
        $baseTotal = 0;
        $syndicTotal = 0;
        $extraTotal = 0;
        $reserveTotal = 0;
        $gasTotal = 0;
        $grandTotal = 0;

        foreach ($residentUnits as $residentUnit) {
            $unitId = $residentUnit->id();
            $baseEqualCents = $baseEqualByUnit[$unitId] ?? 0;
            $syndicCents = $syndicByUnit[$unitId] ?? 0;
            $fractionCents = $fractionByUnit[$unitId] ?? 0;
            $individualCents = $individualByUnit[$unitId] ?? 0;
            $baseCents = $baseEqualCents + $fractionCents + $individualCents;
            $gasCents = $gasByUnit[$unitId] ?? 0;
            $extraCents = $extraPerUnitCents;
            $reserveCents = $reservePerUnitCents;
            $totalCents = $baseCents + $syndicCents + $extraCents + $reserveCents + $gasCents;

            $baseTotal += $baseCents;
            $syndicTotal += $syndicCents;
            $extraTotal += $extraCents;
            $reserveTotal += $reserveCents;
            $gasTotal += $gasCents;
            $grandTotal += $totalCents;

            $units[] = [
                'residentUnitId' => $unitId,
                'unit' => $residentUnit->unit(),
                'idealFraction' => $residentUnit->idealFraction(),
                'baseCents' => $baseCents,
                'syndicCents' => $syndicCents,
                'extraCents' => $extraCents,
                'reserveCents' => $reserveCents,
                'gasCents' => $gasCents,
                'totalCents' => $totalCents,
                // Compatibilidad retroactiva para frontend existente
                // Legacy: en la UI existente este campo representa "despesas previstas" (sin síndico).
                'equalShareCents' => $baseCents,
                'baseEqualShareCents' => $baseEqualCents,
                'fractionShareCents' => $fractionCents,
                'individualNonGasCents' => $individualCents,
                'extraFeeCents' => $extraCents,
                'reserveFundCents' => $reserveCents,
            ];
        }

        $components = [
            'baseTotalCents' => $baseTotal,
            'syndicTotalCents' => $syndicTotal,
            'extraTotalCents' => $extraTotal,
            'reserveTotalCents' => $reserveTotal,
            'gasTotalCents' => $gasTotal,
            'grandTotalCents' => $grandTotal,
            // Legacy alias usado en UI antigua para "despesas previstas" (sin síndico).
            'despesasPrevistasTotalCents' => $baseTotal,
        ];

        $componentsTotalCents = $baseTotal + $syndicTotal + $extraTotal + $reserveTotal + $gasTotal;

        return [
            'units' => $units,
            'components' => $components,
            'totals' => [
                'totalCents' => $grandTotal,
                'componentsTotalCents' => $componentsTotalCents,
                'differenceCents' => $grandTotal - $componentsTotalCents,
            ],
        ];
    }

    /**
     * @param array<int, ResidentUnit> $residentUnits
     *
     * @return array<string, int>
     */
    private function allocateEqualPool(array $residentUnits, int $poolCents): array
    {
        $count = count($residentUnits);

        if ($count === 0) {
            return [];
        }

        $base = (int) floor($poolCents / $count);
        $remainder = $poolCents - ($base * $count);

        if ($remainder > 0) {
            ++$base;
        }

        $out = [];

        foreach ($residentUnits as $unit) {
            $out[$unit->id()] = $base;
        }

        return $out;
    }

    /**
     * @param array<int, ResidentUnit> $residentUnits
     *
     * @return array<string, int>
     */
    private function allocateFractionPool(array $residentUnits, int $poolCents): array
    {
        $count = count($residentUnits);

        if ($count === 0) {
            return [];
        }

        $allocated = array_fill(0, $count, 0);
        $allocatedTotal = 0;
        $idealSum = 0.0;

        foreach ($residentUnits as $unit) {
            if ($unit->idealFraction() > 0) {
                $idealSum += $unit->idealFraction();
            }
        }
        $normalizer = $idealSum > 0 ? $idealSum : 1.0;

        foreach ($residentUnits as $idx => $unit) {
            $raw = $poolCents * ($unit->idealFraction() / $normalizer);
            $floored = (int) floor($raw);
            $allocated[$idx] = $floored;
            $allocatedTotal += $floored;
        }

        $remainder = $poolCents - $allocatedTotal;

        if ($remainder > 0) {
            foreach ($allocated as $idx => $cents) {
                $allocated[$idx] = $cents + 1;
            }
        }

        $out = [];

        foreach ($residentUnits as $idx => $unit) {
            $out[$unit->id()] = $allocated[$idx];
        }

        return $out;
    }
}
