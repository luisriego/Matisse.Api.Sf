<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\CreateRecurringExpense;

use App\Context\Expense\Application\UseCase\EnterExpense\CreateRecurringExpenseCommand;
use App\Context\Expense\Application\UseCase\EnterExpense\CreateRecurringExpenseCommandHandler;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\ValueObject\ExpenseTypeRepository;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Tests\Context\Expense\Domain\ExpenseAmountMother;
use App\Tests\Context\Expense\Domain\ExpenseIdMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateRecurringExpenseCommandHandlerTest extends TestCase
{
    private RecurringExpenseRepository&MockObject $recurringExpenseRepo;
    private ExpenseTypeRepository&MockObject $typeRepo;
    private CreateRecurringExpenseCommandHandler $handler;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->recurringExpenseRepo = $this->createMock(RecurringExpenseRepository::class);
        $this->typeRepo = $this->createMock(ExpenseTypeRepository::class);

        $this->handler = new CreateRecurringExpenseCommandHandler(
            $this->recurringExpenseRepo,
            $this->typeRepo
        );
    }

    /** @test
     * @throws \DateMalformedStringException
     */
    public function test_it_should_create_and_save_recurring_expense(): void
    {
        // Arrange
        $idMother = ExpenseIdMother::create();
        $amountMother = ExpenseAmountMother::create();
        $typeMother = ExpenseTypeMother::create();
        $dueDayMother = 15;

        $monthsOfYear = [1, 6, 12];
        $startDateString = '2025-01-15';
        $endDateString = '2025-12-15';
        $description = 'Monthly subscription';
        $notes = 'Auto-generated';

        $command = new CreateRecurringExpenseCommand(
            $idMother->value(),
            $amountMother->value(),
            $typeMother->id(),
            $dueDayMother,
            $monthsOfYear,
            $startDateString,
            $endDateString,
            $description,
            $notes
        );

        $this->typeRepo
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($typeMother->id())
            ->willReturn($typeMother);

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('save')
            ->with(
                self::callback(function (RecurringExpense $re) use (
                    $idMother, $amountMother, $typeMother,
                    $dueDayMother, $monthsOfYear,
                    $startDateString, $endDateString,
                    $description, $notes
                ): bool {
                    // We only assert the type and that no exception was thrown
                    return $re instanceof RecurringExpense;
                }),
                true
            );

        // Act
        $this->handler->__invoke($command);
    }
}
