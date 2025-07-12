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
use App\Context\Expense\Domain\ValueObject\ExpenseTypeRepository;
use App\Shared\Domain\Event\EventBus;
use App\Tests\Context\Expense\Domain\ExpenseAmountMother;
use App\Tests\Context\Expense\Domain\ExpenseIdMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
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

    /** @test
     * @throws \DateMalformedStringException
     */
    public function test_it_should_create_and_save_recurring_expense(): void
    {
        // Arrange
        $idMother = ExpenseIdMother::create();
        $amountMother = ExpenseAmountMother::create();
        $typeMother = ExpenseTypeMother::create();
        $accountMother = $this->createMock(Account::class);
        $dueDayMother = 15;

        $monthsOfYear = [1, 6, 12];
        $startDateString = '2025-01-15';
        $endDateString = '2025-12-15';
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

        // Mock expectations
        // Se llama 2 veces: una para crear RecurringExpense, otra para crear individual expenses
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
            ->expects(self::exactly(count($monthsOfYear)))
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
}
