<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase;

use App\Context\Slip\Domain\Service\SlipGenerationPolicy;
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
        private readonly SlipGenerationPolicy $generationPolicy,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(SlipGenerationCommand $command): void
    {
        $expenseYear = $command->year();
        $expenseMonth = $command->month();

        // 1. Check if generation is allowed according to business rules.
        $this->generationPolicy->check($expenseYear, $expenseMonth, $command->isForced());

        // 2. Determine date ranges. The due date is for the month after the expenses.
        $expenseRange = DateRange::fromMonth($expenseYear, $expenseMonth);
        $dueDateContext = (new \DateTimeImmutable(sprintf('%d-%d-01', $expenseYear, $expenseMonth)))->modify('+1 month');
        $dueYear = (int)$dueDateContext->format('Y');
        $dueMonth = (int)$dueDateContext->format('m');
        $dueDateRange = DateRange::fromMonth($dueYear, $dueMonth);

        // 3. Delete any existing slips for the due date month before generating new ones.
        $this->slipRepository->deleteByDateRange($dueDateRange);

        // 4. Get all expenses for the period.
        $expenses = $this->expenseRepository->findActiveByDateRange($expenseRange);
        $recurringExpenses = $this->recurringExpenseRepository->findActiveForDateRange($expenseRange);
        $allExpenses = array_merge($expenses, $recurringExpenses);

        // 5. Get all active residential units.
        $residentUnits = $this->residentUnitRepository->findAllActive();

        // 6. Use the service to calculate the distribution.
        $distribution = $this->expenseDistributor->distribute($allExpenses, $residentUnits);

        // 7. Calculate the due date once for the correct month.
        $dueDateTime = SlipDueDate::selectDueDate($dueYear, $dueMonth);
        $dueDate = new SlipDueDate($dueDateTime);

        // 8. Generate a Slip for each residential unit with its calculated amount.
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