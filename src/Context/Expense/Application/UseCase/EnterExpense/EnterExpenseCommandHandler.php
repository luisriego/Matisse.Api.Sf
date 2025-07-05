<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\EnterExpense;

use App\Context\Account\Domain\AccountRepository;
use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDate;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Event\EventBus;
use DateTime;

readonly class EnterExpenseCommandHandler implements CommandHandler
{
    public function __construct(
        private ExpenseRepository $expenseRepo,
        private AccountRepository $accountRepo,
        private ExpenseTypeRepository $typeRepository,
        private EventBus $bus,
    ) {}

    /**
     * @throws \DateMalformedStringException
     */
    public function __invoke(EnterExpenseCommand $command): void
    {
        $id      = new ExpenseId($command->id());
        $amount  = new ExpenseAmount($command->amount());
        $type = $this->typeRepository->findOneByIdOrFail($command->type());
        $account = $this->accountRepo->findOneByIdOrFail($command->accountId());
        $dueDate = new ExpenseDueDate(new DateTime($command->dueDate()));

        $expense = Expense::create($id, $amount, $type, $account, $dueDate);

        $this->expenseRepo->save($expense, false);
        $this->bus->publish(...$expense->pullDomainEvents());
    }
}