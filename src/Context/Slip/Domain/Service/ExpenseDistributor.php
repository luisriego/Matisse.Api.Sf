<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Service;

use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\ResidentUnit\Domain\ResidentUnit;

class ExpenseDistributor
{
    public function distribute(array $expenses, array $residentUnits): array
    {
        if (empty($residentUnits)) {
            return [];
        }

        // Initialize distribution array for each unit.
        $distribution = array_fill_keys(
            array_map(static fn(ResidentUnit $unit) => $unit->id(), $residentUnits),
            0
        );

        // Group expenses by their distribution method.
        $totalsByMethod = [
            ExpenseType::EQUAL => 0,
            ExpenseType::FRACTION => 0,
        ];

        foreach ($expenses as $expense) {
            $expenseType = $expense->type();
            if (null === $expenseType) {
                // This expense has no type, so we cannot determine its distribution method.
                // We will skip it. Consider logging this event for review.
                continue;
            }

            $method = $expenseType->distributionMethod();
            if (isset($totalsByMethod[$method])) {
                $totalsByMethod[$method] += $expense->amount();
            }
            // Note: INDIVIDUAL expenses are ignored for now as they are not part of the collective slip generation.
        }

        // Distribute amounts for each method.
        if ($totalsByMethod[ExpenseType::EQUAL] > 0) {
            $this->distributeEqually($totalsByMethod[ExpenseType::EQUAL], $residentUnits, $distribution);
        }

        if ($totalsByMethod[ExpenseType::FRACTION] > 0) {
            $this->distributeByIdealFraction($totalsByMethod[ExpenseType::FRACTION], $residentUnits, $distribution);
        }

        return $distribution;
    }

    private function distributeByIdealFraction(int $totalAmount, array $residentUnits, array &$distribution): void
    {
        $totalIdealFraction = array_sum(
            array_map(static fn(ResidentUnit $unit) => $unit->idealFraction(), $residentUnits)
        );

        if ($totalIdealFraction <= 0) {
            // Fallback to equal distribution if total fraction is zero or invalid.
            $this->distributeEqually($totalAmount, $residentUnits, $distribution);
            return;
        }

        $distributedAmount = 0;
        foreach ($residentUnits as $unit) {
            $unitShare = ($unit->idealFraction() / $totalIdealFraction) * $totalAmount;
            $amountToAdd = (int) round($unitShare);
            $distribution[$unit->id()] += $amountToAdd;
            $distributedAmount += $amountToAdd;
        }

        // Adjust for rounding errors to ensure the total is exact.
        $remainder = $totalAmount - $distributedAmount;
        if ($remainder !== 0) {
            // Add remainder to the unit with the largest fraction to minimize relative error.
            usort($residentUnits, fn($a, $b) => $b->idealFraction() <=> $a->idealFraction());
            $distribution[$residentUnits[0]->id()] += $remainder;
        }
    }

    private function distributeEqually(int $totalAmount, array $residentUnits, array &$distribution): void
    {
        $unitCount = count($residentUnits);
        if ($unitCount === 0) {
            return;
        }
        $amountPerUnit = (int) floor($totalAmount / $unitCount);
        $remainder = $totalAmount % $unitCount;

        $unitIds = array_keys($distribution);
        foreach ($unitIds as $unitId) {
            $distribution[$unitId] += $amountPerUnit;
        }

        // Distribute the remainder (1 cent at a time) to the first few units.
        for ($i = 0; $i < $remainder; $i++) {
            $distribution[$unitIds[$i]]++;
        }
    }
}