<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Application\UseCase\GetAccountBalance;

use App\Context\Account\Application\UseCase\GetAccountBalance\GetAccountBalanceQuery;
use App\Context\Account\Application\UseCase\GetAccountBalance\GetAccountBalanceQueryHandler;
use App\Context\Account\Domain\Bus\InitialBalanceSet;
use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Expense\Domain\Bus\ExpenseWasEntered;
use App\Context\Income\Domain\Bus\IncomeWasEntered;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Context\Account\Domain\AccountIdMother;
use App\Tests\Context\Expense\Domain\ExpenseAmountMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Context\Income\Domain\IncomeAmountMother;
use App\Tests\Context\Income\Domain\IncomeTypeMother;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetAccountBalanceQueryHandlerTest extends TestCase
{
    private StoredEventRepository&MockObject $storedEventRepository;
    private GetAccountBalanceQueryHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storedEventRepository = $this->createMock(StoredEventRepository::class);
        $this->handler = new GetAccountBalanceQueryHandler($this->storedEventRepository);
    }

    /** @test */
    public function test_it_should_calculate_balance_from_events(): void
    {
        // Arrange
        $accountId = AccountIdMother::create()->value();
        $upToDate = new DateTimeImmutable('2025-10-31');

        // Simulate stored transaction events
        $transactionEvents = [
            $this->createStoredIncomeEvent(
                $accountId,
                IncomeAmountMother::create(100000)->value(),
                '2025-10-01'
            ),
            $this->createStoredExpenseEvent(
                $accountId,
                ExpenseAmountMother::create(20000)->value(),
                '2025-10-15'
            ),
            $this->createStoredIncomeEvent(
                $accountId,
                IncomeAmountMother::create(50000)->value(),
                '2025-10-20'
            ),
        ];

        // Configure consecutive calls for findByEventNamesAndOccurredBetween
        $this->storedEventRepository
            ->expects(self::exactly(2))
            ->method('findByEventNamesAndOccurredBetween')
            ->willReturnOnConsecutiveCalls(
                [], // First call for InitialBalanceSet events
                $transactionEvents // Second call for transaction events
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

        // Configure consecutive calls for findByEventNamesAndOccurredBetween
        $this->storedEventRepository
            ->expects(self::exactly(2))
            ->method('findByEventNamesAndOccurredBetween')
            ->willReturnOnConsecutiveCalls(
                [], // First call for InitialBalanceSet events
                [] // Second call for transaction events
            );

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

        $transactionEvents = [
            $this->createStoredIncomeEvent(
                $accountId,
                IncomeAmountMother::create(100000)->value(),
                '2025-09-01'
            ),
            $this->createStoredExpenseEvent(
                $accountId,
                ExpenseAmountMother::create(30000)->value(),
                '2025-09-15'
            ),
            $this->createStoredIncomeEvent(
                $accountId,
                IncomeAmountMother::create(20000)->value(),
                '2025-10-01'
            ), // This one should be ignored by the handler as its dueDate is after upToDate
        ];

        // Configure consecutive calls for findByEventNamesAndOccurredBetween
        $this->storedEventRepository
            ->expects(self::exactly(2))
            ->method('findByEventNamesAndOccurredBetween')
            ->willReturnOnConsecutiveCalls(
                [], // First call for InitialBalanceSet events
                $transactionEvents // Second call for transaction events
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

        // Mock for InitialBalanceSet events (first call)
        $initialBalanceEvents = [
            $this->createStoredInitialBalanceEvent(
                $accountId,
                $initialBalanceAmount,
                $initialBalanceDate
            ),
        ];

        // Simulate stored transaction events after initial balance date
        $transactionEvents = [
            $this->createStoredIncomeEvent(
                $accountId,
                IncomeAmountMother::create(100000)->value(),
                '2025-02-01'
            ),
            $this->createStoredExpenseEvent(
                $accountId,
                ExpenseAmountMother::create(20000)->value(),
                '2025-03-15'
            ),
            $this->createStoredIncomeEvent(
                $accountId,
                IncomeAmountMother::create(50000)->value(),
                '2025-10-20'
            ),
            // Event after upToDate (should be ignored by the handler)
            $this->createStoredExpenseEvent(
                $accountId,
                ExpenseAmountMother::create(10000)->value(),
                '2025-11-05'
            ),
        ];

        // Configure consecutive calls for findByEventNamesAndOccurredBetween
        $this->storedEventRepository
            ->expects(self::exactly(2))
            ->method('findByEventNamesAndOccurredBetween')
            ->willReturnOnConsecutiveCalls(
                $initialBalanceEvents, // First call for InitialBalanceSet events
                $transactionEvents // Second call for transaction events
            );

        $query = new GetAccountBalanceQuery($accountId, $upToDate);

        // Act
        $result = $this->handler->__invoke($query);

        // Assert
        // Expected balance: 500000 (Initial) + 100000 (Income) - 20000 (Expense) + 50000 (Income) = 630000
        self::assertEquals(630000, $result);
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
            ExpenseWasEntered::eventName(),
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
            InitialBalanceSet::eventName(),
            [
                'amount' => $amount,
                'date' => $date,
            ],
            new DateTimeImmutable($date) // Pass the date as occurredAt
        );
    }
}
