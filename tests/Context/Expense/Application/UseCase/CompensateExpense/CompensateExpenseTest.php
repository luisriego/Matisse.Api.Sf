<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\CompensateExpense;

use App\Context\Expense\Application\UseCase\CompensateExpense\CompensateExpenseCommand;
use App\Context\Expense\Application\UseCase\CompensateExpense\CompensateExpenseCommandHandler;
use App\Context\Expense\Domain\Bus\ExpenseWasCompensated;
use App\Context\Expense\Domain\Bus\ExpenseWasEntered;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Shared\Domain\Event\EventBus;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use PHPUnit\Framework\TestCase;
use Exception;
use Symfony\Component\Uid\Uuid;

class CompensateExpenseTest extends TestCase
{
    private CompensateExpenseCommandHandler $handler;
    private ExpenseRepository $expenseRepository;
    private EventBus $eventBus;

    protected function setUp(): void
    {
        $this->expenseRepository = $this->createMock(ExpenseRepository::class);
        $this->eventBus = $this->createMock(EventBus::class);
        $this->handler = new CompensateExpenseCommandHandler(
            $this->expenseRepository,
            $this->eventBus
        );
    }

    public function test_it_should_compensate_an_expense_and_create_a_new_one(): void
    {
        $originalExpense = ExpenseMother::create();
        $compensatedAmount = 5000; // Example compensated amount
        $command = new CompensateExpenseCommand($originalExpense->id(), $compensatedAmount);

        // Expect findOneByIdOrFail to be called for the original expense
        $this->expenseRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($originalExpense->id())
            ->willReturn($originalExpense);

        // Expect save to be called twice, and verify arguments for each call
        $this->expenseRepository->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function ($expense, $flush) use ($originalExpense, $compensatedAmount) {
                // First call: original expense, amount 0, flush false
                if ($expense->id() === $originalExpense->id() && $expense->amount() === 0 && $flush === false) {
                    return; 
                }
                // Second call: new expense, amount compensatedAmount, flush true
                if ($expense->amount() === $compensatedAmount && $flush === true) {
                    return; 
                }
                $this->fail('Unexpected call to save() with arguments: ' . print_r([$expense->toArray(), $flush], true));
            });

        // Expect remove to be called once
        $this->expenseRepository->expects($this->once())
            ->method('remove')
            ->with($originalExpense, false);

        // Expect publish to be called twice, and verify events for each call
        $this->eventBus->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(function (...$events) {
                // First call: ExpenseWasCompensated
                if (count($events) === 1 && $events[0] instanceof ExpenseWasCompensated) {
                    return;
                }
                // Second call: ExpenseWasEntered
                if (count($events) === 1 && $events[0] instanceof ExpenseWasEntered) {
                    return;
                }
                $this->fail('Unexpected call to publish() with events: ' . print_r($events, true));
            });

        ($this->handler)($command);

        // Assertions for the state of the original expense (after handler execution)
        $this->assertSame(0, $originalExpense->amount());
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

        // Ensure no other methods are called on the repository or event bus
        $this->expenseRepository->expects($this->never())->method('save');
        $this->expenseRepository->expects($this->never())->method('remove');
        $this->eventBus->expects($this->never())->method('publish');
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

        // Ensure no other methods are called on the repository or event bus
        $this->expenseRepository->expects($this->never())->method('save');
        $this->expenseRepository->expects($this->never())->method('remove');
        $this->eventBus->expects($this->never())->method('publish');

        ($this->handler)($command);

        // Assert that the expense amount remains unchanged (since compensate() returned early)
        $this->assertEquals($initialAmount, $expenseWithoutAccount->amount());
    }
}
