<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\CompensateExpense;

use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Shared\Application\CommandHandler;

readonly class CompensateExpenseCommandHandler implements CommandHandler
{
    public function __construct(
        private ExpenseRepository $expenseRepo,
    ) {}

    public function __invoke(CompensateExpenseCommand $command): void
    {
        // 1) reconstitute the aggregate
        $expenseId = new ExpenseId($command->id());
        $expense = $this->expenseRepo->findOneByIdOrFail($expenseId->value());

        $initialAmount = $expense->amount();

        // 2) apply domain logic (records ExpenseWasCompensated)
        $expense->compensate();

        // Check if compensation actually happened by comparing the amount
        if ($expense->amount() === $initialAmount) {
            // If amount hasn't changed, it means compensate() returned early (e.g., no account)
            return;
        }

        // 3) apply corrected amount on the same aggregate to avoid duplicates.
        // This keeps the same expense ID and prevents "compensate + recreate" duplication bugs.
        $newAmount = new ExpenseAmount($command->amount());
        $expense->updateAmount($newAmount->value());
        $this->expenseRepo->save($expense, true);
        // $newExpense->publishDomainEvents($this->bus); // Handled automatically by DomainEventCollectorSubscriber
    }
}
