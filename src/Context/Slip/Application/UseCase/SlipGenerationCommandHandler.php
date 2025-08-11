<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase;

use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Slip\Domain\Service\ExpenseDistributor;
use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\SlipRepository;
use App\Context\Slip\Domain\ValueObject\SlipAmount;
use App\Context\Slip\Domain\ValueObject\SlipDueDate;
use App\Context\Slip\Domain\ValueObject\SlipId;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\ValueObject\DateRange;
use App\Shared\Domain\ValueObject\Uuid;
use DateMalformedStringException;

class SlipGenerationCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly SlipRepository $slipRepository,
        private readonly ExpenseRepository $expenseRepository,
        private readonly RecurringExpenseRepository $recurringExpenseRepository,
        private readonly ResidentUnitRepository $residentUnitRepository,
        private readonly ExpenseDistributor $expenseDistributor,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(SlipGenerationCommand $command): void
    {
        $dateRange = DateRange::fromMonth($command->year(), $command->month());

        // 1. Get all expenses for the period.
        $expenses = $this->expenseRepository->findActiveByDateRange($dateRange);
        $recurringExpenses = $this->recurringExpenseRepository->findActiveForDateRange($dateRange);
        $allExpenses = array_merge($expenses, $recurringExpenses);

        // 2. Get all active and not condo residential units. (form 101 to 501 without condo type)
        $residentUnits = $this->residentUnitRepository->findAllActive();

        // 3. Use the service to calculate the distribution.
        $distribution = $this->expenseDistributor->distribute($allExpenses, $residentUnits);

        // Calculate the due date once, as it's the same for all slips in this batch.
        $dueDate = SlipDueDate::selectDueDate($command->year(), $command->month());
        $dueDate = new SlipDueDate($dueDate);

        // 4. Generate a Slip for each residential unit with its calculated amount.
        foreach ($distribution as $unitId => $amount) {
            $id = new SlipId(Uuid::random()->value());
            $residentUnit = $this->residentUnitRepository->findOneByIdOrFail($unitId);
            $slipAmount = new SlipAmount($amount);
            $slip = Slip::createForUnit($id, $slipAmount, $residentUnit, dueDate: $dueDate);
            $this->slipRepository->save($slip, false);
        }

        $this->slipRepository->flush();
    }
}