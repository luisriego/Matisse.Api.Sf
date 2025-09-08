<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\EnterExpense;

use App\Context\Account\Domain\AccountRepository;
use App\Context\Expense\Application\UseCase\EnterExpense\EnterExpenseWithDescriptionCommand;
use App\Context\Expense\Application\UseCase\EnterExpense\EnterExpenseWithDescriptionCommandHandler;
use App\Context\Expense\Domain\Bus\ExpenseWasEntered;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeRepository;
use App\Shared\Domain\Event\EventBus;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Context\Account\Domain\AccountDescriptionMother;
use PHPUnit\Framework\TestCase;
use Exception;

class EnterExpenseWithDescriptionCommandHandlerTest extends TestCase
{
    private EnterExpenseWithDescriptionCommandHandler $handler;
    private ExpenseRepository $expenseRepository;
    private AccountRepository $accountRepository;
    private ExpenseTypeRepository $expenseTypeRepository;
    private EventBus $eventBus;

    protected function setUp(): void
    {
        $this->expenseRepository = $this->createMock(ExpenseRepository::class);
        $this->accountRepository = $this->createMock(AccountRepository::class);
        $this->expenseTypeRepository = $this->createMock(ExpenseTypeRepository::class);
        $this->eventBus = $this->createMock(EventBus::class);

        $this->handler = new EnterExpenseWithDescriptionCommandHandler(
            $this->expenseRepository,
            $this->accountRepository,
            $this->expenseTypeRepository,
            $this->eventBus
        );
    }

    public function test_it_should_enter_an_expense_with_description(): void
    {
        $expense = ExpenseMother::create();
        $account = AccountMother::create();
        $expenseType = new ExpenseType('type-id', 'CODE', 'Name');
        $description = AccountDescriptionMother::create()->value();

        $command = new EnterExpenseWithDescriptionCommand(
            $expense->id(),
            $expense->amount(),
            $expenseType->id(),
            $account->id(),
            $expense->dueDate()->format('Y-m-d H:i:s'),
            $description
        );

        $this->expenseTypeRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($expenseType->id())
            ->willReturn($expenseType);

        $this->accountRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($account->id())
            ->willReturn($account);

        $this->expenseRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($argExpense) use ($expense, $description) {
                return $argExpense->id() === $expense->id()
                    && $argExpense->amount() === $expense->amount()
                    && $argExpense->description() === $description;
            }), true);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (...$events) {
                return count($events) === 1 && $events[0] instanceof ExpenseWasEntered;
            }));

        ($this->handler)($command);
    }

    public function test_it_should_throw_exception_if_account_not_found(): void
    {
        $expense = ExpenseMother::create();
        $expenseType = new ExpenseType('type-id', 'CODE', 'Name');
        $description = AccountDescriptionMother::create()->value();

        $command = new EnterExpenseWithDescriptionCommand(
            $expense->id(),
            $expense->amount(),
            $expenseType->id(),
            'non-existent-account-id',
            $expense->dueDate()->format('Y-m-d H:i:s'),
            $description
        );

        $this->expenseTypeRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($expenseType->id())
            ->willReturn($expenseType);

        $this->accountRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with('non-existent-account-id')
            ->willThrowException(new Exception('Account not found'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Account not found');

        ($this->handler)($command);

        // Ensure no other methods are called on other mocks
        $this->expenseRepository->expects($this->never())->method('save');
        $this->eventBus->expects($this->never())->method('publish');
    }

    public function test_it_should_throw_exception_if_expense_type_not_found(): void
    {
        $expense = ExpenseMother::create();
        $account = AccountMother::create();
        $description = AccountDescriptionMother::create()->value();

        $command = new EnterExpenseWithDescriptionCommand(
            $expense->id(),
            $expense->amount(),
            'non-existent-type-id',
            $account->id(),
            $expense->dueDate()->format('Y-m-d H:i:s'),
            $description
        );

        $this->expenseTypeRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with('non-existent-type-id')
            ->willThrowException(new Exception('Expense type not found'));

        // AccountRepository should NOT be called if ExpenseTypeRepository throws an exception
        $this->accountRepository->expects($this->never())->method('findOneByIdOrFail');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Expense type not found');

        ($this->handler)($command);

        // Ensure no other methods are called on other mocks
        $this->expenseRepository->expects($this->never())->method('save');
        $this->eventBus->expects($this->never())->method('publish');
    }
}
