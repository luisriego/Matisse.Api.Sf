<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\EnterExpense;

use App\Context\Account\Domain\AccountRepository;
use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseDescription;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDate;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDay;
use App\Context\Expense\Domain\ValueObject\ExpenseEndDate;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Context\Expense\Domain\ValueObject\ExpenseStartDate;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\ValueObject\Uuid;
use DateMalformedStringException;
use DateTime;

use function sprintf;

readonly class CreateRecurringExpenseCommandHandler implements CommandHandler
{
    public function __construct(
        private RecurringExpenseRepository $recurringExpenseRepository,
        private ExpenseTypeRepository $typeRepo,
        protected AccountRepository $accountRepository,
        protected ExpenseRepository $expenseRepository,
        private EventBus $eventBus,
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

        $this->recurringExpenseRepository->save($recurringExpense, false);

        // Collect all events to publish at the end
        $events = [...$recurringExpense->pullDomainEvents()];

        // Now create individual expenses for each month
        $individualExpenses = $this->createIndividualExpenses($recurringExpense, $command);

        // Collect events from individual expenses
        foreach ($individualExpenses as $expense) {
            $events = [...$events, ...$expense->pullDomainEvents()];
        }

        // Flush everything at once
        $this->recurringExpenseRepository->flush();

        // Publish all events after successful persistence
        $this->eventBus->publish(...$events);
    }

    /**
     * @throws DateMalformedStringException
     */
    private function createIndividualExpenses(RecurringExpense $recurringExpense, CreateRecurringExpenseCommand $command): array
    {
        $account = $this->accountRepository->findOneByIdOrFail($command->accountId());
        $expenseType = $this->typeRepo->findOneByIdOrFail($command->type());

        $startDate = new DateTime($command->startDate());
        $year = $startDate->format('Y');

        $expenses = [];

        foreach ($command->monthsOfYear() as $month) {
            $dueDate = (new DateTime())->setDate((int) $year, $month, $command->dueDay())->setTime(0, 30);

            $expense = Expense::create(
                new ExpenseId(Uuid::random()->value()),
                new ExpenseAmount($command->amount()),
                $expenseType,
                $account,
                new ExpenseDueDate($dueDate),
                false,
                new ExpenseDescription(sprintf('%s (%s)', $command->description() ?? 'Recurring expense', $dueDate->format('F'))),
            );

            $expense->setRecurringExpense($recurringExpense);

            $this->expenseRepository->save($expense, false);

            $expenses[] = $expense;
        }

        return $expenses;
    }
}
