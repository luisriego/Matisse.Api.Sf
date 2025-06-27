<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\UpdateExpense;

use App\Context\Expense\Domain\ExpenseAmount;
use App\Context\Expense\Domain\ExpenseDescription;
use App\Context\Expense\Domain\ExpenseDueDate;
use App\Context\Expense\Domain\ExpenseId;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Application\EventBus;
use DateMalformedStringException;
use DateTime;

readonly class UpdateExpenseCommandHandler implements CommandHandler
{
    public function __construct(
        private ExpenseRepository $repository,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(UpdateExpenseCommand $command): void
    {
        $id = new ExpenseId($command->id());
        $expense = $this->repository->findOneByIdOrFail($id->value());

        if (null !== $command->amount()) {
            $amount = new ExpenseAmount($command->amount());
            $expense->updateAmount($amount->value());
        }

        if (null !== $command->dueDate()) {
            $dueDate = new ExpenseDueDate(new DateTime($command->dueDate()));
            $expense->updateDueDate($dueDate->toDateTime());
        }

        if (null !== $command->description()) {
            $description = new ExpenseDescription($command->description());
            $expense->updateDescription($description->value());
        }

        $this->repository->save($expense, true);
    }
}
