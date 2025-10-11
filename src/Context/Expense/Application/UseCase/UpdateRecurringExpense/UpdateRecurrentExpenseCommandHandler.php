<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\UpdateRecurringExpense;

use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeId;
use App\Context\Expense\Domain\ExpenseTypeRepository;
use App\Shared\Application\CommandHandler;
use DateMalformedStringException;
use DateTime;

readonly class UpdateRecurrentExpenseCommandHandler implements CommandHandler
{
    public function __construct(
        private RecurringExpenseRepository $recurringExpenseRepo,
        private ExpenseTypeRepository $expenseTypeRepo,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(UpdateRecurrentExpenseCommand $command): void
    {
        $id = new ExpenseId($command->id());
        $expense = $this->recurringExpenseRepo->findOneByIdOrFail($id->value());

        if (null !== $command->type()) {
            $typeId = new ExpenseTypeId($command->type());
            $type = $this->expenseTypeRepo->findOneByIdOrFail($typeId->value());
        }

        if (null !== $command->amount()) {
            $expense->updateAmount($command->amount());
        }

        if (null !== $command->type()) {
            $expense->updateType($type);
        }

        if (null !== $command->dueDay()) {
            $expense->updateDueDay($command->dueDay());
        }

        if (null !== $command->monthsOfYear()) {
            $expense->updateMonthsOfYear($command->monthsOfYear());
        }

        if (null !== $command->startDate()) {
            $expense->updateStartDate(new DateTime($command->startDate()));
        }

        if (null !== $command->endDate()) {
            $expense->updateEndDate(new DateTime($command->endDate()));
        }

        if (null !== $command->description()) {
            $expense->updateDescription($command->description());
        }

        if (null !== $command->notes()) {
            $expense->updateNotes($command->notes());
        }

        $this->recurringExpenseRepo->save($expense, true);
    }
}
