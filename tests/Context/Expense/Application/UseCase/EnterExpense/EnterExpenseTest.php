<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\EnterExpense;

use App\Context\Expense\Application\UseCase\EnterExpense\EnterExpenseCommand;
use App\Context\Expense\Infrastructure\Http\Controller\EnterExpenseCommandHandler;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseAmountMother;
use App\Tests\Context\Expense\Domain\ExpenseDueDateMother;
use App\Tests\Context\Expense\Domain\ExpenseIdMother;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class EnterExpenseTest extends TestCase
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

    /** @test */
    public function test_it_should_enter_an_expense(): void
    {
        $id = ExpenseIdMother::create();
        $amount = ExpenseAmountMother::create();
        $account = AccountMother::create();
        $dueDate = ExpenseDueDateMother::create();

        $expense = ExpenseMother::create($id, $amount, $account, $dueDate);

        $command = new EnterExpenseCommand($id->value(), $amount->value(), $account->id(), $dueDate->value());

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