<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\EnterExpense;

use App\Context\Account\Domain\AccountRepository;
use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseDescription;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDate;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Context\Expense\Domain\ExpenseTypeRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Event\EventBus;
use DateMalformedStringException;
use DateTime;

readonly class EnterExpenseWithDescriptionCommandHandler implements CommandHandler
{
    public function __construct(
        private ExpenseRepository $expenseRepository,
        private AccountRepository $accountRepository,
        private ExpenseTypeRepository $typeRepository,
        private EventBus $bus,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(EnterExpenseWithDescriptionCommand $command): void
    {
        $id      = new ExpenseId($command->id());
        $amount  = new ExpenseAmount($command->amount());
        $type = $this->typeRepository->findOneByIdOrFail($command->type());
        $account = $this->accountRepository->findOneByIdOrFail($command->accountId());
        $dueDate = new ExpenseDueDate(new DateTime($command->dueDate()));
        $description = new ExpenseDescription($command->description());

        $expense = Expense::create($id, $amount, $type, $account, $dueDate, true, $description);

        $this->expenseRepository->save($expense, true);
        $this->bus->publish(...$expense->pullDomainEvents());
    }
}
