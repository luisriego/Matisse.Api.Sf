<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Account\Domain\AccountRepository;
use App\Context\Expense\Application\UseCase\EnterExpense\EnterExpenseCommand;
use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseAmount;
use App\Context\Expense\Domain\ExpenseDueDate;
use App\Context\Expense\Domain\ExpenseId;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Application\EventBus;
use DateTime;

readonly class EnterExpenseCommandHandler implements CommandHandler
{
    public function __construct(
        private ExpenseRepository $expenseRepo,
        private AccountRepository $accountRepo,
        private EventBus $bus,
    ) {}

    public function __invoke(EnterExpenseCommand $command): void
    {
        $id = new ExpenseId($command->id());
        $amount = new ExpenseAmount($command->amount());
        $account = $this->accountRepo->findOneByIdOrFail($command->accountId());
        $dueDate = new ExpenseDueDate(new DateTime($command->dueDate()));

        $expense = Expense::create($id, $amount, $account, $dueDate);

        $this->expenseRepo->save($expense, true);
        $this->bus->publish(...$expense->pullDomainEvents());
    }
}
