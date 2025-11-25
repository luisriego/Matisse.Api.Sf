<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\AssignAccountTypeToExpenseType;

use App\Context\Account\Domain\AccountRepository;
use App\Context\Expense\Domain\ExpenseTypeRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\ValueObject\Uuid;

final class AssignAccountTypeToExpenseTypeCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly ExpenseTypeRepository $expenseTypeRepository,
        private readonly AccountRepository $accountRepository,
    ) {
    }

    public function __invoke(AssignAccountTypeToExpenseTypeCommand $command): void
    {
        $expenseTypeId = new Uuid($command->getExpenseTypeId());
        $accountTypeId = new Uuid($command->getAccountTypeId());

        $expenseType = $this->expenseTypeRepository->find($expenseTypeId->value());
        $account = $this->accountRepository->find($accountTypeId->value());

        $expenseType->assignAccount($account);

        $this->expenseTypeRepository->save($expenseType);
    }
}
