<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\EnterExpense;

use App\Context\Account\Domain\AccountRepository;
use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseDescription;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDate;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Event\EventBus;
use DateMalformedStringException;
use DateTimeImmutable;

use function sprintf;

final readonly class EnterMonthlyRecurringExpenseCommandHandler implements CommandHandler
{
    public function __construct(
        private RecurringExpenseRepository $recurringExpenseRepository,
        private AccountRepository $accountRepository,
        private ExpenseRepository $expenseRepository,
        private EventBus $eventBus,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(EnterMonthlyRecurringExpenseCommand $command): void
    {
        $recurringExpense = $this->recurringExpenseRepository->findOneByIdOrFail($command->recurringExpenseId());
        $account = $this->accountRepository->findOneByIdOrFail($command->accountId());
        $date = new DateTimeImmutable($command->date());

        $expense = Expense::create(
            new ExpenseId($command->expenseId()), // Use the ID from the command
            new ExpenseAmount($command->amount()),
            $recurringExpense->type(),
            $account,
            new ExpenseDueDate($date),
            true,
            new ExpenseDescription(sprintf(
                '%s (%s)',
                $recurringExpense->description() ?? 'Recurring expense',
                $date->format('F Y'),
            )),
        );

        $expense->setRecurringExpense($recurringExpense);

        $this->expenseRepository->save($expense, true); // Flush immediately

        $this->eventBus->publish(...$expense->pullDomainEvents());
    }
}
