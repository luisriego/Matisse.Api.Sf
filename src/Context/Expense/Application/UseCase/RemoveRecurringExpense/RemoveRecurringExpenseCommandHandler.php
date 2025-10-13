<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\RemoveRecurringExpense;

use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Shared\Application\CommandHandler;

readonly class RemoveRecurringExpenseCommandHandler implements CommandHandler
{
    public function __construct(private RecurringExpenseRepository $repository) {}

    public function __invoke(RemoveRecurringExpenseCommand $command): void
    {
        $id = new ExpenseId($command->id());
        $expense = $this->repository->findOneByIdOrFail($id->value());

        $this->repository->remove($expense, true);
    }
}
