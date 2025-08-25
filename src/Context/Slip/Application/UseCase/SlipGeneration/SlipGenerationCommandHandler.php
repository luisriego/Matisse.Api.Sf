<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\SlipGeneration;

use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Slip\Domain\Service\SlipFactory;
use App\Context\Slip\Domain\Service\SlipGenerationPolicy;
use App\Context\Slip\Domain\SlipRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\ValueObject\DateRange;
use DateMalformedStringException;
use DateTimeImmutable;

use function array_merge;
use function sprintf;

class SlipGenerationCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly SlipRepository $slipRepository,
        private readonly ExpenseRepository $expenseRepository,
        private readonly RecurringExpenseRepository $recurringExpenseRepository,
        private readonly ResidentUnitRepository $residentUnitRepository,
        private readonly SlipGenerationPolicy $generationPolicy,
        private readonly SlipFactory $slipFactory,
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
        $dueDateContext = (new DateTimeImmutable(sprintf('%d-%d-01', $expenseYear, $expenseMonth)))->modify('+1 month');
        $dueYear = (int) $dueDateContext->format('Y');
        $dueMonth = (int) $dueDateContext->format('m');
        $dueDateRange = DateRange::fromMonth($dueYear, $dueMonth);

        // 3. Delete any existing slips for the due date month before generating new ones.
        $this->slipRepository->deleteByDateRange($dueDateRange);

        // 4. Get all expenses for the period.
        $expenses = $this->expenseRepository->findActiveByDateRange($expenseRange);
        $recurringExpenses = $this->recurringExpenseRepository->findActiveForDateRange($expenseRange);
        $allExpenses = array_merge($expenses, $recurringExpenses);

        // 5. Get all active residential units
        $residentUnits = $this->residentUnitRepository->findAllActive();

        // 6. Use the factory to create the slip aggregates.
        $slips = $this->slipFactory->createFromExpensesAndUnits(
            $allExpenses,
            $residentUnits,
            $expenseYear,
            $expenseMonth,
        );

        // 7. Persist the new slips.
        foreach ($slips as $slip) {
            $this->slipRepository->save($slip, false);
        }

        if (!empty($slips)) {
            $this->slipRepository->flush();
        }
    }
}
