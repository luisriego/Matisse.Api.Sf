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
    public function test_it_should_create_and_save_recurring_expense(): void
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
            $notes
        );

        $expectedCount = $this->getExpectedExpenseCount($monthsOfYear, $startDateString);

        // Mock expectations
        $this->typeRepo
            ->expects(self::exactly(2))
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
                self::callback(function (RecurringExpense $re): bool {
                    return $re instanceof RecurringExpense;
                }),
                false
            );

        $this->expenseRepo
            ->expects(self::exactly($expectedCount))
            ->method('save')
            ->with(
                self::anything(),
                false
            );

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('flush');

        $this->eventBus
            ->expects(self::once())
            ->method('publish');

        // Act
        $this->handler->__invoke($command);
    }

    /** @test */
    public function test_it_should_throw_exception_when_expense_type_not_found(): void
    {
        // Arrange
        $idMother = ExpenseIdMother::create();
        $amountMother = ExpenseAmountMother::create();
        $nonExistentTypeId = 'non-existent-type-id';

        $startDateString = (new DateTime())->format('Y-m-d');
        $endDateString = (new DateTime())->modify('+1 year')->format('Y-m-d');

        $command = new CreateRecurringExpenseCommand(
            $idMother->value(),
            $amountMother->value(),
            $nonExistentTypeId,
            'account-123',
            15,
            [1, 6, 12],
            $startDateString,
            $endDateString,
            'description',
            'notes'
        );

        $this->typeRepo
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($nonExistentTypeId)
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
        $idMother = ExpenseIdMother::create();
        $amountMother = ExpenseAmountMother::create();
        $typeMother = ExpenseTypeMother::create();
        $nonExistentAccountId = 'non-existent-account-id';

        $startDateString = (new DateTime())->format('Y-m-d');
        $endDateString = (new DateTime())->modify('+1 year')->format('Y-m-d');

        $command = new CreateRecurringExpenseCommand(
            $idMother->value(),
            $amountMother->value(),
            $typeMother->id(),
            $nonExistentAccountId,
            15,
            [1, 6, 12],
            $startDateString,
            $endDateString,
            'description',
            'notes'
        );

        $this->typeRepo
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($typeMother->id())
            ->willReturn($typeMother);

        $this->accountRepo
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($nonExistentAccountId)
            ->willThrowException(new ResourceNotFoundException('Account not found'));

        // Assert
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Account not found');

        // Act
        $this->handler->__invoke($command);
    }

    /** @test */
    public function test_it_should_handle_single_month_recurring_expense(): void
    {
        // Arrange
        $idMother = ExpenseIdMother::create();
        $amountMother = ExpenseAmountMother::create();
        $typeMother = ExpenseTypeMother::create();
        $accountMother = $this->createMock(Account::class);

        $singleMonth = [6]; // Only June
        $startDateString = (new DateTime())->format('Y-m-d');
        $endDateString = (new DateTime())->modify('+1 year')->format('Y-m-d');

        $command = new CreateRecurringExpenseCommand(
            $idMother->value(),
            $amountMother->value(),
            $typeMother->id(),
            'account-123',
            15,
            $singleMonth,
            $startDateString,
            $endDateString,
            'Single month expense',
            'notes'
        );

        $expectedCount = $this->getExpectedExpenseCount($singleMonth, $startDateString);

        // Mock expectations
        $this->typeRepo
            ->expects(self::exactly(2))
            ->method('findOneByIdOrFail')
            ->willReturn($typeMother);

        $this->accountRepo
            ->expects(self::atLeastOnce())
            ->method('findOneByIdOrFail')
            ->willReturn($accountMother);

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('save');

        $this->expenseRepo
            ->expects(self::exactly($expectedCount))
            ->method('save');

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('flush');

        $this->eventBus
            ->expects(self::once())
            ->method('publish');

        // Act
        $this->handler->__invoke($command);
    }

    /** @test */
    public function test_it_should_handle_all_months_recurring_expense(): void
    {
        // Arrange
        $idMother = ExpenseIdMother::create();
        $amountMother = ExpenseAmountMother::create();
        $typeMother = ExpenseTypeMother::create();
        $accountMother = $this->createMock(Account::class);

        $allMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        $startDateString = (new DateTime())->format('Y-m-d');
        $endDateString = (new DateTime())->modify('+1 year')->format('Y-m-d');

        $command = new CreateRecurringExpenseCommand(
            $idMother->value(),
            $amountMother->value(),
            $typeMother->id(),
            'account-123',
            15,
            $allMonths,
            $startDateString,
            $endDateString,
            'Monthly expense',
            'notes'
        );

        $expectedCount = $this->getExpectedExpenseCount($allMonths, $startDateString);

        // Mock expectations
        $this->typeRepo
            ->expects(self::exactly(2))
            ->method('findOneByIdOrFail')
            ->willReturn($typeMother);

        $this->accountRepo
            ->expects(self::atLeastOnce())
            ->method('findOneByIdOrFail')
            ->willReturn($accountMother);

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('save');

        $this->expenseRepo
            ->expects(self::exactly($expectedCount))
            ->method('save');

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('flush');

        $this->eventBus
            ->expects(self::once())
            ->method('publish');

        // Act
        $this->handler->__invoke($command);
    }

    /** @test */
    public function test_it_should_handle_empty_months_array(): void
    {
        // Arrange
        $idMother = ExpenseIdMother::create();
        $amountMother = ExpenseAmountMother::create();
        $typeMother = ExpenseTypeMother::create();
        $accountMother = $this->createMock(Account::class);

        $emptyMonths = [];
        $startDateString = (new DateTime())->format('Y-m-d');
        $endDateString = (new DateTime())->modify('+1 year')->format('Y-m-d');

        $command = new CreateRecurringExpenseCommand(
            $idMother->value(),
            $amountMother->value(),
            $typeMother->id(),
            'account-123',
            15,
            $emptyMonths,
            $startDateString,
            $endDateString,
            'No months expense',
            'notes'
        );

        // Mock expectations
        $this->typeRepo
            ->expects(self::exactly(2))
            ->method('findOneByIdOrFail')
            ->willReturn($typeMother);

        $this->accountRepo
            ->expects(self::atLeastOnce())
            ->method('findOneByIdOrFail')
            ->willReturn($accountMother);

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('save');

        $this->expenseRepo
            ->expects(self::exactly(0)) // No individual expenses
            ->method('save');

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('flush');

        $this->eventBus
            ->expects(self::once())
            ->method('publish');

        // Act
        $this->handler->__invoke($command);
    }

    /** @test */
    public function test_it_should_handle_invalid_date_format(): void
    {
        // Arrange
        $idMother = ExpenseIdMother::create();
        $amountMother = ExpenseAmountMother::create();
        $typeMother = ExpenseTypeMother::create();

        $endDateString = (new DateTime())->modify('+1 year')->format('Y-m-d');

        $command = new CreateRecurringExpenseCommand(
            $idMother->value(),
            $amountMother->value(),
            $typeMother->id(),
            'account-123',
            15,
            [1, 6, 12],
            'invalid-date-format',
            $endDateString,
            'description',
            'notes'
        );

        $this->typeRepo
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->willReturn($typeMother);

        // Assert
        $this->expectException(DateMalformedStringException::class);

        // Act
        $this->handler->__invoke($command);
    }

    /** @test */
    public function test_it_should_handle_edge_case_due_days(): void
    {
        // Arrange
        $idMother = ExpenseIdMother::create();
        $amountMother = ExpenseAmountMother::create();
        $typeMother = ExpenseTypeMother::create();
        $accountMother = $this->createMock(Account::class);

        $startDateString = (new DateTime())->format('Y-m-d');
        $endDateString = (new DateTime())->modify('+1 year')->format('Y-m-d');
        $months = [2, 4, 6]; // February, April, June (shorter months)

        // Test with day 31 (which doesn't exist in all months)
        $command = new CreateRecurringExpenseCommand(
            $idMother->value(),
            $amountMother->value(),
            $typeMother->id(),
            'account-123',
            31, // Edge case: day 31
            $months,
            $startDateString,
            $endDateString,
            'Edge case due day',
            'notes'
        );

        $expectedCount = $this->getExpectedExpenseCount($months, $startDateString);

        // Mock expectations
        $this->typeRepo
            ->expects(self::exactly(2))
            ->method('findOneByIdOrFail')
            ->willReturn($typeMother);

        $this->accountRepo
            ->expects(self::atLeastOnce())
            ->method('findOneByIdOrFail')
            ->willReturn($accountMother);

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('save');

        $this->expenseRepo
            ->expects(self::exactly($expectedCount))
            ->method('save');

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('flush');

        $this->eventBus
            ->expects(self::once())
            ->method('publish');

        // Act
        $this->handler->__invoke($command);
    }

    /** @test */
    public function test_it_should_handle_minimum_due_day(): void
    {
        // Arrange
        $idMother = ExpenseIdMother::create();
        $amountMother = ExpenseAmountMother::create();
        $typeMother = ExpenseTypeMother::create();
        $accountMother = $this->createMock(Account::class);

        $startDateString = (new DateTime())->format('Y-m-d');
        $endDateString = (new DateTime())->modify('+1 year')->format('Y-m-d');
        $months = [1, 6, 12];

        $command = new CreateRecurringExpenseCommand(
            $idMother->value(),
            $amountMother->value(),
            $typeMother->id(),
            'account-123',
            1, // Minimum due day
            $months,
            $startDateString,
            $endDateString,
            'Minimum due day',
            'notes'
        );

        $expectedCount = $this->getExpectedExpenseCount($months, $startDateString);

        // Mock expectations
        $this->typeRepo
            ->expects(self::exactly(2))
            ->method('findOneByIdOrFail')
            ->willReturn($typeMother);

        $this->accountRepo
            ->expects(self::atLeastOnce())
            ->method('findOneByIdOrFail')
            ->willReturn($accountMother);

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('save');

        $this->expenseRepo
            ->expects(self::exactly($expectedCount))
            ->method('save');

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('flush');

        $this->eventBus
            ->expects(self::once())
            ->method('publish');

        // Act
        $this->handler->__invoke($command);
    }

    /** @test */
    public function test_it_should_handle_zero_amount(): void
    {
        // Arrange
        $idMother = ExpenseIdMother::create();
        $typeMother = ExpenseTypeMother::create();
        $accountMother = $this->createMock(Account::class);

        $startDateString = (new DateTime())->format('Y-m-d');
        $endDateString = (new DateTime())->modify('+1 year')->format('Y-m-d');
        $months = [1, 6, 12];

        $command = new CreateRecurringExpenseCommand(
            $idMother->value(),
            0, // Zero amount
            $typeMother->id(),
            'account-123',
            15,
            $months,
            $startDateString,
            $endDateString,
            'Zero amount expense',
            'notes'
        );

        $expectedCount = $this->getExpectedExpenseCount($months, $startDateString);

        // Mock expectations
        $this->typeRepo
            ->expects(self::exactly(2))
            ->method('findOneByIdOrFail')
            ->willReturn($typeMother);

        $this->accountRepo
            ->expects(self::atLeastOnce())
            ->method('findOneByIdOrFail')
            ->willReturn($accountMother);

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('save');

        $this->expenseRepo
            ->expects(self::exactly($expectedCount))
            ->method('save');

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('flush');

        $this->eventBus
            ->expects(self::once())
            ->method('publish');

        // Act
        $this->handler->__invoke($command);
    }

    /** @test */
    public function test_it_should_handle_null_description_and_notes(): void
    {
        // Arrange
        $idMother = ExpenseIdMother::create();
        $amountMother = ExpenseAmountMother::create();
        $typeMother = ExpenseTypeMother::create();
        $accountMother = $this->createMock(Account::class);

        $startDateString = (new DateTime())->format('Y-m-d');
        $endDateString = (new DateTime())->modify('+1 year')->format('Y-m-d');
        $months = [1, 6, 12];

        $command = new CreateRecurringExpenseCommand(
            $idMother->value(),
            $amountMother->value(),
            $typeMother->id(),
            'account-123',
            15,
            $months,
            $startDateString,
            $endDateString,
            '', // Empty description
            '' // Empty notes
        );

        $expectedCount = $this->getExpectedExpenseCount($months, $startDateString);

        // Mock expectations
        $this->typeRepo
            ->expects(self::exactly(2))
            ->method('findOneByIdOrFail')
            ->willReturn($typeMother);

        $this->accountRepo
            ->expects(self::atLeastOnce())
            ->method('findOneByIdOrFail')
            ->willReturn($accountMother);

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('save');

        $this->expenseRepo
            ->expects(self::exactly($expectedCount))
            ->method('save');

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('flush');

        $this->eventBus
            ->expects(self::once())
            ->method('publish');

        // Act
        $this->handler->__invoke($command);
    }

    /** @test */
    public function test_it_should_handle_leap_year_february(): void
    {
        // Arrange
        $idMother = ExpenseIdMother::create();
        $amountMother = ExpenseAmountMother::create();
        $typeMother = ExpenseTypeMother::create();
        $accountMother = $this->createMock(Account::class);

        $currentYear = (int) (new DateTime())->format('Y');
        $leapYear = $currentYear;
        while (!((new DateTime())->setDate($leapYear, 1, 1)->format('L'))) {
            $leapYear++;
        }

        $startDateString = (new DateTime())->setDate($leapYear, 1, 1)->format('Y-m-d');
        $endDateString = (new DateTime())->setDate($leapYear, 12, 31)->format('Y-m-d');
        $months = [2]; // February only

        $command = new CreateRecurringExpenseCommand(
            $idMother->value(),
            $amountMother->value(),
            $typeMother->id(),
            'account-123',
            29, // February 29th
            $months,
            $startDateString,
            $endDateString,
            'Leap year test',
            'notes'
        );

        $expectedCount = $this->getExpectedExpenseCount($months, $startDateString);

        // Mock expectations
        $this->typeRepo
            ->expects(self::exactly(2))
            ->method('findOneByIdOrFail')
            ->willReturn($typeMother);

        $this->accountRepo
            ->expects(self::atLeastOnce())
            ->method('findOneByIdOrFail')
            ->willReturn($accountMother);

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('save');

        $this->expenseRepo
            ->expects(self::exactly($expectedCount))
            ->method('save');

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('flush');

        $this->eventBus
            ->expects(self::once())
            ->method('publish');

        // Act
        $this->handler->__invoke($command);
    }

    /** @test */
    public function test_it_should_handle_repository_save_failure(): void
    {
        // Arrange
        $idMother = ExpenseIdMother::create();
        $amountMother = ExpenseAmountMother::create();
        $typeMother = ExpenseTypeMother::create();

        $startDateString = (new DateTime())->format('Y-m-d');
        $endDateString = (new DateTime())->modify('+1 year')->format('Y-m-d');

        $command = new CreateRecurringExpenseCommand(
            $idMother->value(),
            $amountMother->value(),
            $typeMother->id(),
            'account-122',
            15,
            [1, 6, 12],
            $startDateString,
            $endDateString,
            'description',
            'notes'
        );

        $this->typeRepo
            ->expects(self::once()) // Only called once before the save fails
            ->method('findOneByIdOrFail')
            ->with($typeMother->id())
            ->willReturn($typeMother);

        $this->accountRepo
            ->expects(self::never()) // Never called because save fails first
            ->method('findOneByIdOrFail');

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('save')
            ->willThrowException(new \Exception('Database connection failed'));

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database connection failed');

        // Act
        $this->handler->__invoke($command);
    }

    /** @test */
    public function test_it_should_handle_flush_failure(): void
    {
        // Arrange
        $idMother = ExpenseIdMother::create();
        $amountMother = ExpenseAmountMother::create();
        $typeMother = ExpenseTypeMother::create();
        $accountMother = $this->createMock(Account::class);

        $startDateString = (new DateTime())->format('Y-m-d');
        $endDateString = (new DateTime())->modify('+1 year')->format('Y-m-d');
        $months = [1, 6, 12];

        $command = new CreateRecurringExpenseCommand(
            $idMother->value(),
            $amountMother->value(),
            $typeMother->id(),
            'account-123',
            15,
            $months,
            $startDateString,
            $endDateString,
            'description',
            'notes'
        );

        $expectedCount = $this->getExpectedExpenseCount($months, $startDateString);

        $this->typeRepo
            ->expects(self::exactly(2))
            ->method('findOneByIdOrFail')
            ->willReturn($typeMother);

        $this->accountRepo
            ->expects(self::atLeastOnce())
            ->method('findOneByIdOrFail')
            ->willReturn($accountMother);

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('save');

        $this->expenseRepo
            ->expects(self::exactly($expectedCount))
            ->method('save');

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('flush')
            ->willThrowException(new Exception('Flush failed'));

        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Flush failed');

        // Act
        $this->handler->__invoke($command);
    }

    /** @test */
    public function test_it_should_not_create_expenses_for_past_months_when_date_is_in_the_past(): void
    {
        // Arrange
        $idMother = ExpenseIdMother::create();
        $amountMother = ExpenseAmountMother::create();
        $typeMother = ExpenseTypeMother::create();
        $accountMother = $this->createMock(Account::class);

        $pastDateString = (new DateTime())->modify('-1 day')->format('Y-m-d'); // Date in the past
        $months = [1, 6, 12];

        $command = new CreateRecurringExpenseCommand(
            $idMother->value(),
            $amountMother->value(),
            $typeMother->id(),
            'account-123',
            15,
            $months,
            $pastDateString,
            (new DateTime())->modify('+1 year')->format('Y-m-d'),
            'Past recurring expense',
            'notes'
        );

        $expectedCount = $this->getExpectedExpenseCount($months, $pastDateString);

        $this->typeRepo
            ->expects(self::exactly(2))
            ->method('findOneByIdOrFail')
            ->willReturn($typeMother);

        $this->accountRepo
            ->expects(self::atLeastOnce())
            ->method('findOneByIdOrFail')
            ->willReturn($accountMother);

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('save');

        $this->expenseRepo
            ->expects(self::exactly($expectedCount))
            ->method('save');

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('flush');

        $this->eventBus
            ->expects(self::once())
            ->method('publish');

        // Act
        $this->handler->__invoke($command);
    }

    /** @test */
    public function test_it_should_not_create_expenses_for_past_months(): void
    {
        // Arrange
        $idMother = ExpenseIdMother::create();
        $amountMother = ExpenseAmountMother::create();
        $typeMother = ExpenseTypeMother::create();
        $accountMother = $this->createMock(Account::class);
        $dueDayMother = 15;

        $now = new DateTime();
        $currentMonth = (int) $now->format('n');

        // Create a list of months including past, current, and future months
        $monthsOfYear = [];
        for ($i = -2; $i <= 2; $i++) {
            $month = $currentMonth + $i;
            if ($month >= 1 && $month <= 12) {
                $monthsOfYear[] = $month;
            }
        }
        sort($monthsOfYear);

        $startDateString = $now->format('Y-m-d');
        $endDateString = (new DateTime())->modify('+1 year')->format('Y-m-d');
        $description = 'Recurring expense with past months';
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
            $notes
        );

        $expectedExpenseCount = $this->getExpectedExpenseCount($monthsOfYear, $startDateString);

        // Mock expectations
        $this->typeRepo
            ->expects(self::exactly(2))
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
                self::callback(function (RecurringExpense $re): bool {
                    return $re instanceof RecurringExpense;
                }),
                false
            );

        $this->expenseRepo
            ->expects(self::exactly($expectedExpenseCount)) // <-- The important assertion
            ->method('save')
            ->with(
                self::anything(),
                false
            );

        $this->recurringExpenseRepo
            ->expects(self::once())
            ->method('flush');

        $this->eventBus
            ->expects(self::once())
            ->method('publish');

        // Act
        $this->handler->__invoke($command);
    }

    /**
     * @throws Exception
     */
    private function getExpectedExpenseCount(array $monthsOfYear, string $startDateString): int
    {
        $startDate = new DateTime($startDateString);
        $startYear = (int)$startDate->format('Y');
        $currentYear = (int)(new DateTime())->format('Y');
        $currentMonth = (int)(new DateTime())->format('n');

        if ($startYear < $currentYear) {
            return 0;
        }

        if ($startYear > $currentYear) {
            return count($monthsOfYear);
        }

        $expectedExpenseCount = 0;
        foreach ($monthsOfYear as $month) {
            if ($month >= $currentMonth) {
                $expectedExpenseCount++;
            }
        }

        return $expectedExpenseCount;
    }
}
