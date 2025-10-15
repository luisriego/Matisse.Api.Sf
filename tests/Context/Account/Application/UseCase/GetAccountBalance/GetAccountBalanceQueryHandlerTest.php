<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Application\UseCase\GetAccountBalance;

use App\Context\Account\Application\UseCase\GetAccountBalance\GetAccountBalanceQuery;
use App\Context\Account\Application\UseCase\GetAccountBalance\GetAccountBalanceQueryHandler;
use App\Context\EventStore\Domain\StoredEvent;
use App\Context\Income\Domain\Bus\IncomeWasEntered;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Context\Account\Domain\AccountIdMother;
use App\Tests\Context\Expense\Domain\ExpenseAmountMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Context\Income\Domain\IncomeAmountMother;
use App\Tests\Context\Income\Domain\IncomeTypeMother;
use App\Tests\Context\Shared\Infrastructure\Persistence\InMemoryStoredEventRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class GetAccountBalanceQueryHandlerTest extends TestCase
{
    private InMemoryStoredEventRepository $storedEventRepository;
    private GetAccountBalanceQueryHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storedEventRepository = new InMemoryStoredEventRepository();
        $this->handler = new GetAccountBalanceQueryHandler($this->storedEventRepository);
    }

    protected function tearDown(): void
    {
        $this->storedEventRepository->clear();
        parent::tearDown();
    }

    /** @test */
    public function test_it_should_calculate_balance_from_events(): void
    {
        // Arrange
        $accountId = AccountIdMother::create()->value();
        $upToDate = new DateTimeImmutable('2025-10-31');

        // Populate the in-memory repository with events
        $this->storedEventRepository->save(
            $this->createStoredIncomeEvent(
                $accountId,
                IncomeAmountMother::create(100000)->value(),
                '2025-10-01'
            )
        );
        $this->storedEventRepository->save(
            $this->createStoredExpenseEvent(
                $accountId,
                ExpenseAmountMother::create(20000)->value(),
                '2025-10-15'
            )
        );
        $this->storedEventRepository->save(
            $this->createStoredIncomeEvent(
                $accountId,
                IncomeAmountMother::create(50000)->value(),
                '2025-10-20'
            )
        );

        $query = new GetAccountBalanceQuery($accountId, $upToDate);

        // Act
        $result = $this->handler->__invoke($query);

        // Assert
        // Expected balance: 100000 (Salary) - 20000 (Rent) + 50000 (Bonus) = 130000
        self::assertEquals(130000, $result);
    }

    /** @test */
    public function test_it_should_return_zero_if_no_events(): void
    {
        // Arrange
        $accountId = AccountIdMother::create()->value();
        $upToDate = new DateTimeImmutable('2025-10-31');

        // No events saved in the repository

        $query = new GetAccountBalanceQuery($accountId, $upToDate);

        // Act
        $result = $this->handler->__invoke($query);

        // Assert
        self::assertEquals(0, $result);
    }

    /** @test */
    public function test_it_should_handle_events_with_different_dates(): void
    {
        // Arrange
        $accountId = AccountIdMother::create()->value();
        $upToDate = new DateTimeImmutable('2025-09-30');

        // Populate the in-memory repository with events
        $this->storedEventRepository->save(
            $this->createStoredIncomeEvent(
                $accountId,
                IncomeAmountMother::create(100000)->value(),
                '2025-09-01'
            )
        );
        $this->storedEventRepository->save(
            $this->createStoredExpenseEvent(
                $accountId,
                ExpenseAmountMother::create(30000)->value(),
                '2025-09-15'
            )
        );
        // This event should be ignored by the handler as its dueDate is after upToDate
        $this->storedEventRepository->save(
            $this->createStoredIncomeEvent(
                $accountId,
                IncomeAmountMother::create(20000)->value(),
                '2025-10-01'
            )
        );

        $query = new GetAccountBalanceQuery($accountId, $upToDate);

        // Act
        $result = $this->handler->__invoke($query);

        // Assert
        // Expected balance: 100000 (Salary Sep) - 30000 (Rent Sep) = 70000
        self::assertEquals(70000, $result);
    }

    /** @test */
    public function test_it_should_calculate_balance_with_initial_balance_event(): void
    {
        // Arrange
        $accountId = AccountIdMother::create()->value();
        $upToDate = new DateTimeImmutable('2025-10-31');
        $initialBalanceAmount = 500000;
        $initialBalanceDate = '2025-01-01';

        // Populate the in-memory repository with events
        $this->storedEventRepository->save(
            $this->createStoredInitialBalanceEvent(
                $accountId,
                $initialBalanceAmount,
                $initialBalanceDate
            )
        );
        $this->storedEventRepository->save(
            $this->createStoredIncomeEvent(
                $accountId,
                IncomeAmountMother::create(100000)->value(),
                '2025-02-01'
            )
        );
        $this->storedEventRepository->save(
            $this->createStoredExpenseEvent(
                $accountId,
                ExpenseAmountMother::create(20000)->value(),
                '2025-03-15'
            )
        );
        $this->storedEventRepository->save(
            $this->createStoredIncomeEvent(
                $accountId,
                IncomeAmountMother::create(50000)->value(),
                '2025-10-20'
            )
        );
        // Event after upToDate (should be ignored by the handler)
        $this->storedEventRepository->save(
            $this->createStoredExpenseEvent(
                $accountId,
                ExpenseAmountMother::create(10000)->value(),
                '2025-11-05'
            )
        );

        $query = new GetAccountBalanceQuery($accountId, $upToDate);

        // Act
        $result = $this->handler->__invoke($query);

        // Assert
        // Expected balance: 500000 (Initial) + 100000 (Income) - 20000 (Expense) + 50000 (Income) = 630000
        self::assertEquals(630000, $result);
    }

    /** @test */
    public function test_it_should_not_include_events_from_other_accounts(): void
    {
        // Arrange
        $accountId = AccountIdMother::create()->value();
        $otherAccountId = AccountIdMother::create()->value();
        $upToDate = new DateTimeImmutable('2025-10-31');

        // Events for the target account
        $this->storedEventRepository->save(
            $this->createStoredIncomeEvent(
                $accountId,
                IncomeAmountMother::create(100000)->value(),
                '2025-10-01'
            )
        );

        // Events for another account (should be ignored)
        $this->storedEventRepository->save(
            $this->createStoredIncomeEvent(
                $otherAccountId,
                IncomeAmountMother::create(500000)->value(),
                '2025-10-05'
            )
        );
        $this->storedEventRepository->save(
            $this->createStoredExpenseEvent(
                $otherAccountId,
                ExpenseAmountMother::create(100000)->value(),
                '2025-10-10'
            )
        );

        $query = new GetAccountBalanceQuery($accountId, $upToDate);

        // Act
        $result = $this->handler->__invoke($query);

        // Assert
        // Only events from $accountId should be considered. Expected: 100000
        self::assertEquals(100000, $result);
    }

    private function createStoredIncomeEvent(string $accountId, int $amount, string $dueDate): StoredEvent
    {
        return StoredEvent::create(
            $accountId,
            IncomeWasEntered::eventName(),
            [
                'amount' => $amount,
                'type' => IncomeTypeMother::create()->id(),
                'accountId' => $accountId,
                'dueDate' => $dueDate,
                'description' => 'Income Description',
                'residentUnitId' => Uuid::random()->value(),
            ],
            new DateTimeImmutable($dueDate) // Pass the dueDate as occurredAt
        );
    }

    private function createStoredExpenseEvent(string $accountId, int $amount, string $dueDate): StoredEvent
    {
        return StoredEvent::create(
            $accountId,
            'expense.entered', // Using literal to avoid dependency on Expense context
            [
                'amount' => $amount,
                'type' => ExpenseTypeMother::create()->id(),
                'accountId' => $accountId,
                'dueDate' => $dueDate,
                'description' => 'Expense Description',
                'residentUnitId' => Uuid::random()->value(),
            ],
            new DateTimeImmutable($dueDate) // Pass the dueDate as occurredAt
        );
    }

    private function createStoredInitialBalanceEvent(string $accountId, int $amount, string $date): StoredEvent
    {
        return StoredEvent::create(
            $accountId,
            'account.initial_balance.set', // Using literal to avoid dependency on Account context
            [
                'amount' => $amount,
                'date' => $date,
            ],
            new DateTimeImmutable($date) // Pass the date as occurredAt
        );
    }
}
