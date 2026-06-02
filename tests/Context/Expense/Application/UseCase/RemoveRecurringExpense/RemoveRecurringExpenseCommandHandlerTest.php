<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\RemoveRecurringExpense;

use App\Context\Expense\Application\UseCase\RemoveRecurringExpense\RemoveRecurringExpenseCommand;
use App\Context\Expense\Application\UseCase\RemoveRecurringExpense\RemoveRecurringExpenseCommandHandler;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Tests\Context\Expense\Domain\ExpenseIdMother;
use App\Tests\Context\Expense\Domain\RecurringExpenseMother;
use DateMalformedStringException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RemoveRecurringExpenseCommandHandlerTest extends TestCase
{
    private MockObject&RecurringExpenseRepository $repository;
    private RemoveRecurringExpenseCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(RecurringExpenseRepository::class);
        $this->handler = new RemoveRecurringExpenseCommandHandler($this->repository);
    }

    /** @test
     * @throws DateMalformedStringException
     */
    public function testItRemovesExistingRecurringExpense(): void
    {
        // Arrange
        $recurringExpense = RecurringExpenseMother::create();
        $id = new ExpenseId($recurringExpense->id());

        $command = new RemoveRecurringExpenseCommand($id->value());

        $this->repository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($id->value())
            ->willReturn($recurringExpense);

        $this->repository
            ->expects(self::once())
            ->method('remove')
            ->with($recurringExpense, true);

        // Act
        ($this->handler)($command);
    }

    /**
     * @test
     */
    public function testItPropagatesExceptionWhenNotFound(): void
    {
        // Arrange
        $idValue = ExpenseIdMother::create()->value();
        $command = new RemoveRecurringExpenseCommand($idValue);

        $this->repository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($idValue)
            ->willThrowException(new RuntimeException('Not found'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not found');

        // Act
        ($this->handler)($command);
    }
}
