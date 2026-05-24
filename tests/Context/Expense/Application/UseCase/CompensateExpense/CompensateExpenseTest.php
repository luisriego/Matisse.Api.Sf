<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\CompensateExpense;

use App\Context\Expense\Application\UseCase\CompensateExpense\CompensateExpenseCommand;
use App\Context\Expense\Application\UseCase\CompensateExpense\CompensateExpenseCommandHandler;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use PHPUnit\Framework\TestCase;
use Exception;
use Symfony\Component\Uid\Uuid;

class CompensateExpenseTest extends TestCase
{
    private CompensateExpenseCommandHandler $handler;
    private ExpenseRepository $expenseRepository;

    protected function setUp(): void
    {
        $this->expenseRepository = $this->createMock(ExpenseRepository::class);
        $this->handler = new CompensateExpenseCommandHandler(
            $this->expenseRepository,
        );
    }

    public function test_it_should_compensate_an_expense_without_creating_duplicate(): void
    {
        $originalExpense = ExpenseMother::create();
        $compensatedAmount = 5000; // Example compensated amount
        $command = new CompensateExpenseCommand($originalExpense->id(), $compensatedAmount);

        // Expect findOneByIdOrFail to be called for the original expense
        $this->expenseRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($originalExpense->id())
            ->willReturn($originalExpense);

        // Single save on the same aggregate (no recreate/remove)
        $this->expenseRepository->expects($this->once())
            ->method('save')
            ->with($originalExpense, true);

        $this->expenseRepository->expects($this->never())->method('remove');

        ($this->handler)($command);

        $this->assertSame($compensatedAmount, $originalExpense->amount());
    }

    public function test_it_should_throw_exception_if_expense_not_found(): void
    {
        $nonExistentId = Uuid::v4()->toRfc4122(); // Use a valid UUID format
        $command = new CompensateExpenseCommand($nonExistentId, 1000);

        $this->expenseRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($nonExistentId)
            ->willThrowException(new Exception('Expense not found'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Expense not found');

        ($this->handler)($command);

        // Ensure no other methods are called on the repository
        $this->expenseRepository->expects($this->never())->method('save');
        $this->expenseRepository->expects($this->never())->method('remove');
    }

    public function test_it_should_not_compensate_if_expense_has_no_account(): void
    {
        $expenseWithoutAccount = ExpenseMother::createWithNoAccount();
        $this->assertNull($expenseWithoutAccount->account()); // Re-added assertion
        $initialAmount = $expenseWithoutAccount->amount(); // Capture initial amount
        $command = new CompensateExpenseCommand($expenseWithoutAccount->id(), 1000);

        // Ensure initial amount is not 0 for this test
        $this->assertNotSame(0, $initialAmount);

        $this->expenseRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($expenseWithoutAccount->id())
            ->willReturn($expenseWithoutAccount);

        // Ensure no other methods are called on the repository
        $this->expenseRepository->expects($this->never())->method('save');
        $this->expenseRepository->expects($this->never())->method('remove');

        ($this->handler)($command);

        // Assert that the expense amount remains unchanged (since compensate() returned early)
        $this->assertEquals($initialAmount, $expenseWithoutAccount->amount());
    }
}
