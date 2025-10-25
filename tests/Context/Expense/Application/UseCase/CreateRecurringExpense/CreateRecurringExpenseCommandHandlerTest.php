<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\CreateRecurringExpense;

use App\Context\Account\Domain\Account;
use App\Context\Account\Domain\AccountRepository;
use App\Context\Expense\Application\UseCase\EnterExpense\CreateRecurringExpenseCommand;
use App\Context\Expense\Application\UseCase\EnterExpense\CreateRecurringExpenseCommandHandler;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\Expense\Domain\ExpenseTypeRepository;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Tests\Context\Expense\Domain\ExpenseAmountMother;
use App\Tests\Context\Expense\Domain\ExpenseIdMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use DateMalformedStringException;
use DateTime;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateRecurringExpenseCommandHandlerTest extends TestCase
{
    private RecurringExpenseRepository&MockObject $recurringExpenseRepo;
    private ExpenseTypeRepository&MockObject $typeRepo;
    private AccountRepository&MockObject $accountRepo;
    private ExpenseRepository&MockObject $expenseRepo;
    private EventBus&MockObject $eventBus;
    private CreateRecurringExpenseCommandHandler $handler;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->recurringExpenseRepo = $this->createMock(RecurringExpenseRepository::class);
        $this->typeRepo = $this->createMock(ExpenseTypeRepository::class);
        $this->accountRepo = $this->createMock(AccountRepository::class);
        $this->expenseRepo = $this->createMock(ExpenseRepository::class);
        $this->eventBus = $this->createMock(EventBus::class);

        $this->handler = new CreateRecurringExpenseCommandHandler(
            $this->recurringExpenseRepo,
            $this->typeRepo,
            $this->accountRepo,
            $this->expenseRepo,
            $this->eventBus
        );
    }

    /** @test */
    public function test_it_should_create_and_save_recurring_expense_with_predefined_amount(): void
    {
        // Arrange
        $idMother = ExpenseIdMother::create();
        $amountMother = ExpenseAmountMother::create();
        $typeMother = ExpenseTypeMother::create();
        $accountMother = $this->createMock(Account::class);
        $dueDayMother = 15;

        $monthsOfYear = [1, 6, 12];
        $startDateString = (new DateTime())->format('Y-m-d');
        $endDateString = (new DateTime())->modify('+1 year')->format('Y-m-d');
        $description = 'Monthly subscription';
        $notes = 'Auto-generated';
        $accountId = 'account-123';

        $command = new CreateRecurringExpenseCommand(
            $idMother->value(),
            $amountMother->value(),
            $typeMother->id(),
            $accountId,
            $dueDayMother,
            $monthsOfYear,
            $startDateString,
            $endDateString,
            $description,
            $notes,
            true // hasPredefinedAmount
        );

        $expectedCount = $this->getExpectedExpenseCount($monthsOfYear, $startDateString);

        // Mock expectations
        $this->typeRepo
            ->expects(self::atLeastOnce())
            ->method('findOneByIdOrFail')
            ->with($typeMother->id())
            ->willReturn($typeMother);

        $this->accountRepo
            ->expects(self::atLeastOnce())
            ->method('findOneByIdOrFail')
            ->with($accountId)
            ->willReturn($accountMother);

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('save')
            ->with(
                self::callback(function (RecurringExpense $re) use ($idMother, $amountMother, $description) {
                    self::assertEquals($idMother->value(), $re->id());
                    self::assertEquals($amountMother->value(), $re->amount());
                    self::assertEquals($description, $re->description());
                    self::assertTrue($re->hasPredefinedAmount());
                    return true;
                }),
                false
            );

        $this->expenseRepo
            ->expects(self::exactly($expectedCount))
            ->method('save');

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('flush');

        $this->eventBus
            ->expects($this->atLeastOnce())
            ->method('publish');

        // Act
        $this->handler->__invoke($command);
    }

    /** @test */
    public function test_it_should_create_recurring_expense_without_predefined_amount(): void
    {
        // Arrange
        $idMother = ExpenseIdMother::create();
        $typeMother = ExpenseTypeMother::create();
        $accountId = 'account-123';

        $command = new CreateRecurringExpenseCommand(
            $idMother->value(),
            0, // Amount is 0 when not predefined
            $typeMother->id(),
            $accountId,
            10,
            [1, 2, 3],
            (new DateTime())->format('Y-m-d'),
            (new DateTime())->modify('+1 year')->format('Y-m-d'),
            'Service without predefined amount',
            'Notes here',
            false // hasPredefinedAmount
        );

        // Mock expectations
        $this->typeRepo
            ->expects(self::once()) // Only called once for the recurring expense
            ->method('findOneByIdOrFail')
            ->with($typeMother->id())
            ->willReturn($typeMother);

        // We should NOT try to find an account if no individual expenses are created
        $this->accountRepo
            ->expects(self::never())
            ->method('findOneByIdOrFail');

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('save')
            ->with(
                self::callback(function (RecurringExpense $re) use ($idMother) {
                    self::assertEquals($idMother->value(), $re->id());
                    self::assertFalse($re->hasPredefinedAmount());
                    return true;
                }),
                false
            );

        // We should NOT create individual expenses
        $this->expenseRepo
            ->expects(self::never())
            ->method('save');

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('flush');

        $this->eventBus
            ->expects($this->atLeastOnce())
            ->method('publish');

        // Act
        $this->handler->__invoke($command);
    }

    /** @test */
    public function test_it_should_throw_exception_when_expense_type_not_found(): void
    {
        // Arrange
        $command = new CreateRecurringExpenseCommand(
            ExpenseIdMother::create()->value(),
            100,
            'non-existent-type-id',
            'account-123',
            15,
            [1, 6, 12],
            (new DateTime())->format('Y-m-d'),
            (new DateTime())->modify('+1 year')->format('Y-m-d'),
            'description',
            'notes',
            true
        );

        $this->typeRepo
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with('non-existent-type-id')
            ->willThrowException(new ResourceNotFoundException('ExpenseType not found'));

        // Assert
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('ExpenseType not found');

        // Act
        $this->handler->__invoke($command);
    }

    /** @test */
    public function test_it_should_throw_exception_when_account_not_found(): void
    {
        // Arrange
        $typeMother = ExpenseTypeMother::create();
        $command = new CreateRecurringExpenseCommand(
            ExpenseIdMother::create()->value(),
            100,
            $typeMother->id(),
            'non-existent-account-id',
            15,
            [1, 6, 12],
            (new DateTime())->format('Y-m-d'),
            (new DateTime())->modify('+1 year')->format('Y-m-d'),
            'description',
            'notes',
            true
        );

        $this->typeRepo
            ->expects(self::atLeastOnce())
            ->method('findOneByIdOrFail')
            ->with($typeMother->id())
            ->willReturn($typeMother);

        $this->accountRepo
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with('non-existent-account-id')
            ->willThrowException(new ResourceNotFoundException('Account not found'));

        // Assert
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Account not found');

        // Act
        $this->handler->__invoke($command);
    }

    // ... (other tests remain the same, but should also be updated to be more specific if needed)

    /**
     * @throws Exception
     */
    private function getExpectedExpenseCount(array $monthsOfYear, string $startDateString): int
    {
        $startDate = new DateTime($startDateString);
        $startYear = (int)$startDate->format('Y');
        $currentYear = (int)(new DateTime())->format('Y');

        // If start year is not the current year, the filter in the handler doesn't apply.
        if ($startYear !== $currentYear) {
            return count($monthsOfYear);
        }

        // If start year is the current year, count only current and future months.
        $currentMonth = (int)(new DateTime())->format('n');
        $expectedExpenseCount = 0;
        foreach ($monthsOfYear as $month) {
            if ($month >= $currentMonth) {
                $expectedExpenseCount++;
            }
        }

        return $expectedExpenseCount;
    }
}
