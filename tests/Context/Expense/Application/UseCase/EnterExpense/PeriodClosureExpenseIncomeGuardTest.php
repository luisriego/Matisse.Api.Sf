<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Domain\Guard;

use App\Context\Expense\Application\UseCase\EnterExpense\EnterExpenseCommand;
use App\Context\Expense\Application\UseCase\UpdateExpense\UpdateExpenseCommand;
use App\Context\Income\Application\UseCase\EnterIncome\EnterIncomeCommand;
use App\Context\Income\Application\UseCase\UpdateIncome\UpdateIncomeCommand;
use App\Context\Slip\Domain\Exception\PeriodAlreadyClosedException;
use App\Context\Slip\Domain\Guard\PeriodClosureExpenseIncomeGuard;
use App\Context\Slip\Domain\PeriodClosureRepository;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(PeriodClosureExpenseIncomeGuard::class)]
final class PeriodClosureExpenseIncomeGuardTest extends TestCase
{
    private PeriodClosureExpenseIncomeGuard $guard;
    private PeriodClosureRepository&MockObject $repository;

    public function setUp(): void
    {
        $this->repository = $this->createMock(PeriodClosureRepository::class);
        $this->guard = new PeriodClosureExpenseIncomeGuard($this->repository);
    }

    public function test_enter_expense_open_period_passes(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('existsForMonth')
            ->with(2025, 6)
            ->willReturn(false);

        $command = new EnterExpenseCommand(
            id: 'exp-1',
            amount: 100,
            type: 'FIXED',
            accountId: 'acc-1',
            dueDate: '2025-06-15',
            isActive: true,
            description: 'Test',
        );

        $this->guard->__invoke($command); // no exception
    }

    public function test_enter_expense_closed_period_throws(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('existsForMonth')
            ->with(2025, 4)
            ->willReturn(true);

        $command = new EnterExpenseCommand(
            id: 'exp-1',
            amount: 100,
            type: 'FIXED',
            accountId: 'acc-1',
            dueDate: '2025-04-15', // April is closed
            isActive: true,
            description: 'Test',
        );

        $this->expectException(PeriodAlreadyClosedException::class);
        $this->expectExceptionMessage('Period 2025-04 is already closed. No modifications are allowed.');
        $this->guard->__invoke($command);
    }

    public function test_enter_income_open_period_passes(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('existsForMonth')
            ->with(2025, 6)
            ->willReturn(false);

        $command = new EnterIncomeCommand(
            id: 'inc-1',
            amount: 200,
            residentUnitId: null,
            type: 'RENT',
            accountId: 'acc-1',
            dueDate: '2025-06-15',
            description: null,
        );

        $this->guard->__invoke($command); // no exception
    }

    public function test_enter_income_closed_period_throws(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('existsForMonth')
            ->with(2025, 4)
            ->willReturn(true);

        $command = new EnterIncomeCommand(
            id: 'inc-1',
            amount: 200,
            residentUnitId: null,
            type: 'RENT',
            accountId: 'acc-1',
            dueDate: '2025-04-15', // April is closed
            description: null,
        );

        $this->expectException(PeriodAlreadyClosedException::class);
        $this->expectExceptionMessage('Period 2025-04 is already closed. No modifications are allowed.');
        $this->guard->__invoke($command);
    }

    public function test_update_expense_with_due_date_open_period_passes(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('existsForMonth')
            ->with(2025, 6)
            ->willReturn(false);

        $command = new UpdateExpenseCommand(
            id: 'some-id',
            dueDate: '2025-06-15',
            description: null,
        );

        $this->guard->__invoke($command); // no exception
    }

    public function test_update_expense_with_due_date_closed_period_throws(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('existsForMonth')
            ->with(2025, 4)
            ->willReturn(true);

        $command = new UpdateExpenseCommand(
            id: 'some-id',
            dueDate: '2025-04-15', // April is closed
            description: null,
        );

        $this->expectException(PeriodAlreadyClosedException::class);
        $this->expectExceptionMessage('Period 2025-04 is already closed. No modifications are allowed.');
        $this->guard->__invoke($command);
    }

    public function test_update_expense_without_due_date_passes(): void
    {
        $this->repository
            ->expects($this->never())
            ->method('existsForMonth');

        $command = new UpdateExpenseCommand(
            id: 'some-id',
            dueDate: null,
            description: 'New description',
        );

        $this->guard->__invoke($command); // no exception
    }

    public function test_update_income_with_due_date_open_period_passes(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('existsForMonth')
            ->with(2025, 6)
            ->willReturn(false);

        $command = new UpdateIncomeCommand(
            id: 'some-id',
            dueDate: '2025-06-15',
            description: null,
        );

        $this->guard->__invoke($command); // no exception
    }

    public function test_update_income_with_due_date_closed_period_throws(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('existsForMonth')
            ->with(2025, 4)
            ->willReturn(true);

        $command = new UpdateIncomeCommand(
            id: 'some-id',
            dueDate: '2025-04-15', // April is closed
            description: null,
        );

        $this->expectException(PeriodAlreadyClosedException::class);
        $this->expectExceptionMessage('Period 2025-04 is already closed. No modifications are allowed.');
        $this->guard->__invoke($command);
    }

    public function test_update_income_without_due_date_passes(): void
    {
        $this->repository
            ->expects($this->never())
            ->method('existsForMonth');

        $command = new UpdateIncomeCommand(
            id: 'some-id',
            dueDate: null,
            description: 'New description',
        );

        $this->guard->__invoke($command); // no exception
    }

    public function test_compensate_expense_open_period_passes(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('existsForMonth')
            ->with(2025, 6)
            ->willReturn(false);

        // Use a mock Expense aggregate for the guard test
        $expense = $this->createMock(\App\Context\Expense\Domain\Expense::class);
        $expense
            ->method('dueDate')
            ->willReturn(new DateTime('2025-06-15'));

        $this->guard->__invoke($expense); // no exception
    }

    public function test_compensate_expense_closed_period_throws(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('existsForMonth')
            ->with(2025, 4)
            ->willReturn(true);

        // Use a mock Expense aggregate for the guard test
        $expense = $this->createMock(\App\Context\Expense\Domain\Expense::class);
        $expense
            ->method('dueDate')
            ->willReturn(new DateTime('2025-04-15')); // April is closed

        $this->expectException(PeriodAlreadyClosedException::class);
        $this->expectExceptionMessage('Period 2025-04 is already closed. No modifications are allowed.');
        $this->guard->__invoke($expense);
    }
}
