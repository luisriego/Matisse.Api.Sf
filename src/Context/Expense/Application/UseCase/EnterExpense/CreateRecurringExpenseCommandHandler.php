<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\EnterExpense;

use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDay;
use App\Context\Expense\Domain\ValueObject\ExpenseEndDate;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Context\Expense\Domain\ValueObject\ExpenseStartDate;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeRepository;
use App\Shared\Application\CommandHandler;
use DateMalformedStringException;

readonly class CreateRecurringExpenseCommandHandler implements CommandHandler
{
    public function __construct(
        private RecurringExpenseRepository $recurringExpenseRepo,
        private ExpenseTypeRepository $typeRepo,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(CreateRecurringExpenseCommand $command): void
    {
        $id = new ExpenseId($command->id());
        $amount = new ExpenseAmount($command->amount());
        $type = $this->typeRepo->findOneByIdOrFail($command->type());
        $dueDay = new ExpenseDueDay($command->dueDay());
        $monthsOfYear = $command->monthsOfYear();
        $startDate = ExpenseStartDate::from($command->startDate());
        $endDate = ExpenseEndDate::from($command->endDate());
        $description = $command->description();
        $notes = $command->notes();

        $recurringExpense = RecurringExpense::create(
            $id,
            $amount,
            $type,
            $dueDay,
            $monthsOfYear,
            $startDate,
            $endDate,
            $description,
            $notes,
        );

        $this->recurringExpenseRepo->save($recurringExpense, true);
    }
}
