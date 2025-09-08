<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\PayedAtExpense;

use App\Context\Expense\Application\UseCase\PayedAtExpense\ExpensePayedCommandHandler;
use App\Context\Expense\Application\UseCase\PayedAtExpense\PayedAtExpenseCommand;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use PHPUnit\Framework\TestCase;
use Exception;

class ExpensePayedCommandHandlerTest extends TestCase
{
    private ExpensePayedCommandHandler $handler;
    private ExpenseRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ExpenseRepository::class);
        $this->handler = new ExpensePayedCommandHandler($this->repository);
    }

    public function test_it_should_mark_an_expense_as_paid(): void
    {
        $expense = ExpenseMother::create();
        $command = new PayedAtExpenseCommand($expense->id());

        $this->repository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($expense->id())
            ->willReturn($expense);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($expense, true);

        ($this->handler)($command);

        $this->assertNotNull($expense->paidAt());
    }

    public function test_it_should_throw_exception_if_expense_not_found(): void
    {
        $command = new PayedAtExpenseCommand('non-existent-id');

        $this->repository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with('non-existent-id')
            ->willThrowException(new Exception('Expense not found'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Expense not found');

        ($this->handler)($command);
    }

    public function test_it_should_not_repay_an_already_paid_expense(): void
    {
        $expense = ExpenseMother::create();
        $expense->markAsPaid(); // Mark it as paid initially
        $initialPaidAt = $expense->paidAt();

        $command = new PayedAtExpenseCommand($expense->id());

        $this->repository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($expense->id())
            ->willReturn($expense);

        // Expect that save() is NOT called
        $this->repository->expects($this->never())
            ->method('save');

        ($this->handler)($command);

        // Assert that paidAt remains the same (no change)
        $this->assertSame($initialPaidAt, $expense->paidAt());
    }
}
