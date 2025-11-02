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
    private MockObject|StoredEventRepository $storedEventRepository;
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
        $defaultStartDate = new DateTimeImmutable('1900-01-01');

        $invocationCount = 0;
        $this->storedEventRepository->expects($this->exactly(2))
            ->method('findByEventTypesAndOccurredBetweenAndAggregateId')
            ->willReturnCallback(function (array $eventTypes, DateTimeImmutable $startDate, ?DateTimeImmutable $endDate, string $aggregateId) use (&$invocationCount, $accountId, $upToDate, $defaultStartDate) {
                $invocationCount++;
                if ($invocationCount === 1) {
                    // First call: for initial balance events
                    $this->assertEquals([InitialBalanceSet::eventName()], $eventTypes);
                    $this->assertEquals($defaultStartDate->format('Y-m-d'), $startDate->format('Y-m-d'));
                    $this->assertEquals($upToDate->format('Y-m-d'), $endDate->format('Y-m-d'));
                    $this->assertEquals($accountId, $aggregateId);
                    return []; // No initial balance event in this specific test setup
                } elseif ($invocationCount === 2) {
                    // Second call: for transaction events
                    $this->assertEquals([ExpenseWasEntered::eventName(), IncomeWasEntered::eventName()], $eventTypes);
                    $this->assertEquals($defaultStartDate->format('Y-m-d'), $startDate->format('Y-m-d'));
                    $this->assertEquals($upToDate->format('Y-m-d'), $endDate->format('Y-m-d'));
                    $this->assertEquals($accountId, $aggregateId);
                    return [
                        $this->createStoredIncomeEvent($accountId, IncomeAmountMother::create(100000)->value(), '2025-10-01'),
                        $this->createStoredExpenseEvent($accountId, ExpenseWasEntered::eventName(), ExpenseAmountMother::create(20000)->value(), '2025-10-15'),
                        $this->createStoredIncomeEvent($accountId, IncomeAmountMother::create(50000)->value(), '2025-10-20'),
                    ];
                }
                return []; // Should not happen
            });

        $query = new GetAccountBalanceQuery($accountId, $upToDate);

        // Act
        $result = $this->handler->__invoke($query);

        // Assert
        self::assertEquals(130000, $result);
    }

    /** @test */
    public function test_it_should_return_zero_if_no_events(): void
    {
        // Arrange
        $accountId = AccountIdMother::create()->value();
        $upToDate = new DateTimeImmutable('2025-10-31');
        $defaultStartDate = new DateTimeImmutable('1900-01-01');

        $invocationCount = 0;
        $this->storedEventRepository->expects($this->exactly(2))
            ->method('findByEventTypesAndOccurredBetweenAndAggregateId')
            ->willReturnCallback(function (array $eventTypes, DateTimeImmutable $startDate, ?DateTimeImmutable $endDate, string $aggregateId) use (&$invocationCount, $accountId, $upToDate, $defaultStartDate) {
                $invocationCount++;
                if ($invocationCount === 1) {
                    // First call: for initial balance events
                    $this->assertEquals([InitialBalanceSet::eventName()], $eventTypes);
                    $this->assertEquals($defaultStartDate->format('Y-m-d'), $startDate->format('Y-m-d'));
                    $this->assertEquals($upToDate->format('Y-m-d'), $endDate->format('Y-m-d'));
                    $this->assertEquals($accountId, $aggregateId);
                    return []; // No initial balance event
                } elseif ($invocationCount === 2) {
                    // Second call: for transaction events
                    $this->assertEquals([ExpenseWasEntered::eventName(), IncomeWasEntered::eventName()], $eventTypes);
                    $this->assertEquals($defaultStartDate->format('Y-m-d'), $startDate->format('Y-m-d'));
                    $this->assertEquals($upToDate->format('Y-m-d'), $endDate->format('Y-m-d'));
                    $this->assertEquals($accountId, $aggregateId);
                    return []; // No transaction events
                }
                return []; // Should not happen
            });

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
        $defaultStartDate = new DateTimeImmutable('1900-01-01');

        $invocationCount = 0;
        $this->storedEventRepository->expects($this->exactly(2))
            ->method('findByEventTypesAndOccurredBetweenAndAggregateId')
            ->willReturnCallback(function (array $eventTypes, DateTimeImmutable $startDate, ?DateTimeImmutable $endDate, string $aggregateId) use (&$invocationCount, $accountId, $upToDate, $defaultStartDate) {
                $invocationCount++;
                if ($invocationCount === 1) {
                    // First call: for initial balance events
                    $this->assertEquals([InitialBalanceSet::eventName()], $eventTypes);
                    $this->assertEquals($defaultStartDate->format('Y-m-d'), $startDate->format('Y-m-d'));
                    $this->assertEquals($upToDate->format('Y-m-d'), $endDate->format('Y-m-d'));
                    $this->assertEquals($accountId, $aggregateId);
                    return []; // No initial balance event
                } elseif ($invocationCount === 2) {
                    // Second call: for transaction events
                    $this->assertEquals([ExpenseWasEntered::eventName(), IncomeWasEntered::eventName()], $eventTypes);
                    $this->assertEquals($defaultStartDate->format('Y-m-d'), $startDate->format('Y-m-d'));
                    $this->assertEquals($upToDate->format('Y-m-d'), $endDate->format('Y-m-d'));
                    $this->assertEquals($accountId, $aggregateId);
                    return [
                        $this->createStoredIncomeEvent($accountId, IncomeAmountMother::create(100000)->value(), '2025-09-01'),
                        $this->createStoredExpenseEvent($accountId, ExpenseWasEntered::eventName(), ExpenseAmountMother::create(30000)->value(), '2025-09-15'),
                    ];
                }
                return []; // Should not happen
            });

        $query = new GetAccountBalanceQuery($accountId, $upToDate);

        // Act
        $result = $this->handler->__invoke($query);

        // Assert
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
        $initialBalanceEvent = $this->createStoredInitialBalanceEvent($accountId, $initialBalanceAmount, $initialBalanceDate);
        $defaultStartDate = new DateTimeImmutable('1900-01-01');

        $invocationCount = 0;
        $this->storedEventRepository->expects($this->exactly(2))
            ->method('findByEventTypesAndOccurredBetweenAndAggregateId')
            ->willReturnCallback(function (array $eventTypes, DateTimeImmutable $startDate, ?DateTimeImmutable $endDate, string $aggregateId) use (&$invocationCount, $accountId, $upToDate, $defaultStartDate, $initialBalanceEvent) {
                $invocationCount++;
                if ($invocationCount === 1) {
                    // First call: for initial balance events
                    $this->assertEquals([InitialBalanceSet::eventName()], $eventTypes);
                    $this->assertEquals($defaultStartDate->format('Y-m-d'), $startDate->format('Y-m-d'));
                    $this->assertEquals($upToDate->format('Y-m-d'), $endDate->format('Y-m-d'));
                    $this->assertEquals($accountId, $aggregateId);
                    return [$initialBalanceEvent];
                } elseif ($invocationCount === 2) {
                    // Second call: for transaction events
                    $this->assertEquals([ExpenseWasEntered::eventName(), IncomeWasEntered::eventName()], $eventTypes);
                    $this->assertEquals((new DateTimeImmutable($initialBalanceEvent->toDomainEvent()->date()))->format('Y-m-d'), $startDate->format('Y-m-d'));
                    $this->assertEquals($upToDate->format('Y-m-d'), $endDate->format('Y-m-d'));
                    $this->assertEquals($accountId, $aggregateId);
                    return [
                        $this->createStoredIncomeEvent($accountId, IncomeAmountMother::create(100000)->value(), '2025-02-01'),
                        $this->createStoredExpenseEvent($accountId, ExpenseWasEntered::eventName(), ExpenseAmountMother::create(20000)->value(), '2025-03-15'),
                        $this->createStoredIncomeEvent($accountId, IncomeAmountMother::create(50000)->value(), '2025-10-20'),
                    ];
                }
                return []; // Should not happen
            });

        $query = new GetAccountBalanceQuery($accountId, $upToDate);

        // Act
        $result = $this->handler->__invoke($query);

        // Assert
        self::assertEquals(630000, $result);
    }

    /** @test */
    public function test_it_should_not_include_events_from_other_accounts(): void
    {
        // Arrange
        $accountId = AccountIdMother::create()->value();
        $otherAccountId = AccountIdMother::create()->value();
        $upToDate = new DateTimeImmutable('2025-10-31');
        $defaultStartDate = new DateTimeImmutable('1900-01-01');

        $invocationCount = 0;
        $this->storedEventRepository->expects($this->exactly(2))
            ->method('findByEventTypesAndOccurredBetweenAndAggregateId')
            ->willReturnCallback(function (array $eventTypes, DateTimeImmutable $startDate, ?DateTimeImmutable $endDate, string $aggregateId) use (&$invocationCount, $accountId, $upToDate, $defaultStartDate) {
                $invocationCount++;
                if ($invocationCount === 1) {
                    // First call: for initial balance events for the target account
                    $this->assertEquals([InitialBalanceSet::eventName()], $eventTypes);
                    $this->assertEquals($defaultStartDate->format('Y-m-d'), $startDate->format('Y-m-d'));
                    $this->assertEquals($upToDate->format('Y-m-d'), $endDate->format('Y-m-d'));
                    $this->assertEquals($accountId, $aggregateId);
                    return []; // No initial balance event for target account
                } elseif ($invocationCount === 2) {
                    // Second call: for transaction events for the target account
                    $this->assertEquals([ExpenseWasEntered::eventName(), IncomeWasEntered::eventName()], $eventTypes);
                    $this->assertEquals($defaultStartDate->format('Y-m-d'), $startDate->format('Y-m-d'));
                    $this->assertEquals($upToDate->format('Y-m-d'), $endDate->format('Y-m-d'));
                    $this->assertEquals($accountId, $aggregateId);
                    return [
                        $this->createStoredIncomeEvent($accountId, IncomeAmountMother::create(100000)->value(), '2025-10-01'),
                    ];
                }
                return []; // Should not happen
            });

        $query = new GetAccountBalanceQuery($accountId, $upToDate);

        // Act
        $result = $this->handler->__invoke($query);

        // Assert
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
            new DateTimeImmutable($dueDate)
        );
    }

    private function createStoredExpenseEvent(string $accountId, string $eventName, int $amount, string $dueDate): StoredEvent
    {
        return StoredEvent::create(
            $accountId,
            $eventName,
            [
                'amount' => $amount,
                'type' => ExpenseTypeMother::create()->id(),
                'accountId' => $accountId,
                'dueDate' => $dueDate,
                'description' => 'Expense Description',
                'residentUnitId' => Uuid::random()->value(),
            ],
            new DateTimeImmutable($dueDate)
        );
    }

    private function createStoredInitialBalanceEvent(string $accountId, int $amount, string $date): StoredEvent
    {
        return StoredEvent::create(
            $accountId,
            InitialBalanceSet::eventName(), // Use eventName() here
            [
                'amount' => $amount,
                'date' => $date,
            ],
            new DateTimeImmutable($date)
        );
    }
}
