<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\EnterExpense;

use App\Context\Account\Domain\AccountRepository;
use App\Context\Expense\Application\UseCase\EnterExpense\EnterExpenseCommand;
use App\Context\Expense\Application\UseCase\EnterExpense\EnterExpenseCommandHandler;
use App\Context\Expense\Domain\Bus\ExpenseWasEntered;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeRepository;
use App\Shared\Domain\Event\EventBus;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseAmountMother;
use App\Tests\Context\Expense\Domain\ExpenseDueDateMother;
use App\Tests\Context\Expense\Domain\ExpenseIdMother;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class EnterExpenseCommandHandlerTest extends TestCase
{
    private EnterExpenseCommandHandler $commandHandler;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandHandler = $this->createMock(EnterExpenseCommandHandler::class);
    }

    /** @test
     * @throws \DateMalformedStringException
     */
    public function test_it_should_enter_an_expense(): void
    {
        $id = ExpenseIdMother::create();
        $amount = ExpenseAmountMother::create();
        $type =  ExpenseTypeMother::create();
        $account = AccountMother::create();
        $dueDate = ExpenseDueDateMother::create();

        $expense = ExpenseMother::create($id, $amount, $account, $dueDate);

        $command = new EnterExpenseCommand($id->value(), $amount->value(), $type->id(), $account->id(), $dueDate->value(), true);

        $this->commandHandler
            ->expects($this->once())
            ->method('__invoke')
            ->with($command);

        $this->commandHandler->__invoke($command);

        $this->assertSame($id->value(), $expense->id());
        $this->assertSame($amount->value(), $expense->amount());
        $this->assertSame($account, $expense->account());
        $this->assertSame($dueDate->value(), $expense->dueDate()->format('Y-m-d'));
    }
}