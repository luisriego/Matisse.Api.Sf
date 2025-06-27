<?php

namespace App\Context\Expense\Application\UseCase\PayedAtExpense;

use App\Context\Expense\Domain\ExpenseRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Application\EventBus;

readonly class ExpensePayedCommandHandler implements CommandHandler
{
    public function __construct(private ExpenseRepository $repository) {}

    public function __invoke(PayedAtExpenseCommand $command): void
    {
        $expense = $this->repository->findOneByIdOrFail($command->id());
        $expense->markAsPaid();

        $this->repository->save($expense, true);
    }
}