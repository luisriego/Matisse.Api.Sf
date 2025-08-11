<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Service;

use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\ResidentUnit\Domain\ResidentUnit;

class ExpenseDistributor
{
    public function distribute(array $expenses, array $residentUnits): array
    {
        if (count($residentUnits) === 0) {
            return [];
        }

        $totalAmount = 0;
        foreach ($expenses as $expense) {
            $totalAmount += $expense->amount();
        }

        // Simple Distribution Logic: equitation for all units.
        // Here we can implement more complex logic if needed.
        $amountPerUnit = (int) floor($totalAmount / count($residentUnits));

        $distribution = [];
        foreach ($residentUnits as $unit) {
            $distribution[$unit->id()] = $amountPerUnit;
        }

        return $distribution;
    }
}