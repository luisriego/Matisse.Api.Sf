<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Service;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\ValueObject\SlipAmount;
use App\Context\Slip\Domain\ValueObject\SlipDueDate;
use App\Context\Slip\Domain\ValueObject\SlipId;
use App\Shared\Domain\ValueObject\Uuid;

readonly class SlipFactory
{
    public function __construct(
        private ExpenseDistributor $expenseDistributor
    ) {
    }

    /**
     * @param array          $allExpenses
     * @param ResidentUnit[] $residentUnits
     * @param int            $expenseYear
     * @param int            $expenseMonth
     * @return Slip[]
     * @throws \DateMalformedStringException
     */
    public function createFromExpensesAndUnits(array $allExpenses, array $residentUnits, int $expenseYear, int $expenseMonth): array
    {
        if (empty($residentUnits) || empty($allExpenses)) {
            return [];
        }

        $distribution = $this->expenseDistributor->distribute($allExpenses, $residentUnits);

        $dueDateContext = (new \DateTimeImmutable(sprintf('%d-%d-01', $expenseYear, $expenseMonth)))->modify('+1 month');
        $dueYear = (int)$dueDateContext->format('Y');
        $dueMonth = (int)$dueDateContext->format('m');
        $dueDateTime = SlipDueDate::selectDueDate($dueYear, $dueMonth);
        $dueDate = new SlipDueDate($dueDateTime);

        $unitMap = [];
        foreach ($residentUnits as $unit) {
            $unitMap[$unit->id()] = $unit;
        }

        $slips = [];
        foreach ($distribution as $unitId => $amount) {
            $id = new SlipId(Uuid::random()->value());
            $residentUnit = $unitMap[$unitId];
            $slipAmount = new SlipAmount($amount);
            $slips[] = Slip::createForUnit($id, $slipAmount, $residentUnit, $dueDate);
        }

        return $slips;
    }
}