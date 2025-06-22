<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\UpdateExpense;

use App\Context\Expense\Application\UseCase\UpdateExpense\UpdateExpenseCommand;
use App\Context\Expense\Application\UseCase\UpdateExpense\UpdateExpenseCommandHandler;
use App\Tests\Context\Expense\Domain\ExpenseIdMother;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Context\Expense\ExpenseModuleUnitTestCase;

final class UpdateExpenseCommandHandlerTest extends ExpenseModuleUnitTestCase
{
    private UpdateExpenseCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new UpdateExpenseCommandHandler(
            $this->repository(),
            $this->eventBus()
        );
    }

    /** @test
     * @throws \DateMalformedStringException
     */
    public function test_it_should_update_expense_with_all_fields(): void
    {
        // Arrange
        $id = ExpenseIdMother::create(); // Use the mother object to create valid UUID
        $expense = ExpenseMother::create($id);

        $newAmount = 1000;
        $newDueDate = '2023-12-31';
        $newDescription = 'Updated description';

        $command = new UpdateExpenseCommand(
            $id->value(),
            $newAmount,
            $newDueDate,
            $newDescription
        );

        $this->shouldFindOneByIdOrFail($id->value(), $expense);
        $this->shouldSave($expense);
        $this->shouldPublishDomainEvents($expense->pullDomainEvents());

        // Act
        $this->handler->__invoke($command);
    }

    /** @test */
    public function test_it_should_update_only_provided_fields(): void
    {
        // Arrange
        $id = ExpenseIdMother::create(); // Use the mother object to create valid UUID
        $expense = ExpenseMother::create($id);

        $command = new UpdateExpenseCommand(
            $id->value(),
            null,  // Don't update amount
            '2023-12-31', // Update due date
            null   // Don't update description
        );

        $this->shouldFindOneByIdOrFail($id->value(), $expense);
        $this->shouldSave($expense);
        $this->shouldPublishDomainEvents($expense->pullDomainEvents());

        // Act
        $this->handler->__invoke($command);
    }
}