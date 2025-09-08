<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\CompensateExpense;

use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseDescription;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDate;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Event\EventBus;
use Symfony\Component\Uid\Uuid;

readonly class CompensateExpenseCommandHandler implements CommandHandler
{
    public function __construct(
        private ExpenseRepository $expenseRepo,
        private EventBus $bus,
    ) {}

    public function __invoke(CompensateExpenseCommand $command): void
    {
        // 1) reconstitute the aggregate
        $expenseId = new ExpenseId($command->id());
        $expense = $this->expenseRepo->findOneByIdOrFail($expenseId->value());

        // 2) apply domain logic (records ExpenseWasCompensated)
        $expense->compensate();

        // Check if any events were recorded, meaning compensation actually happened
        $domainEvents = $expense->pullDomainEvents();

        if (empty($domainEvents)) {
            // If no events, it means compensate() returned early (e.g., no account)
            return;
        }

        // 3) persist new state
        $this->expenseRepo->save($expense, false);

        // 4) publish all new domain events
        $this->bus->publish(...$domainEvents);

        // 5) now I need to recreate the Expense with the compensated amount $command->amount()
        $newExpense = Expense::create(
            new ExpenseId(Uuid::v4()->toRfc4122()),
            new ExpenseAmount($command->amount()),
            $expense->type(),
            $expense->account(),
            new ExpenseDueDate($expense->dueDate()),
            true, // Assuming compensated expense is active
            $expense->description() ? new ExpenseDescription($expense->description()) : null,
        );

        // 6) remove the old one, then save and publish the new expense
        $this->expenseRepo->remove($expense, false);
        $this->expenseRepo->save($newExpense, true);
        $this->bus->publish(...$newExpense->pullDomainEvents());
    }
}
