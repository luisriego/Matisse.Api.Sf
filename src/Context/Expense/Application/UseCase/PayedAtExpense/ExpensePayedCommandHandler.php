<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\PayedAtExpense;

use App\Context\Expense\Domain\ExpenseRepository;
use App\Shared\Application\CommandHandler;

readonly class ExpensePayedCommandHandler implements CommandHandler
{
    public function __construct(private ExpenseRepository $repository) {}

    public function __invoke(PayedAtExpenseCommand $command): void
    {
        $expense = $this->repository->findOneByIdOrFail($command->id());

        // Only mark as paid and save if it's not already paid
        if (null === $expense->paidAt()) {
            $expense->markAsPaid();
            $this->repository->save($expense, true);
        }
    }
}
