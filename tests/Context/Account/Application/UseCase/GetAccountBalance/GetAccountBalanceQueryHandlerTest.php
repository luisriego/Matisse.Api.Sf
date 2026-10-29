<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Application\UseCase\GetAccountBalance;

use App\Context\Account\Application\UseCase\GetAccountBalance\GetAccountBalanceQuery;
use App\Context\Account\Application\UseCase\GetAccountBalance\GetAccountBalanceQueryHandler;
use App\Context\Account\Domain\Event\InitialBalanceSet;
use App\Context\EventStore\Domain\DomainEventRegistry;
use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Expense\Domain\Event\ExpenseWasCompensated;
use App\Context\Expense\Domain\Event\ExpenseWasEntered;
use App\Context\Income\Domain\Event\IncomeWasEntered;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Context\Account\Domain\AccountIdMother;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetAccountBalanceQueryHandlerTest extends TestCase
{
    private MockObject|StoredEventRepository $storedEventRepository;
    private DomainEventRegistry|MockObject $domainEventRegistry;
    private GetAccountBalanceQueryHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storedEventRepository = $this->createMock(StoredEventRepository::class);
        $this->domainEventRegistry = $this->createMock(DomainEventRegistry::class);
        $this->handler = new GetAccountBalanceQueryHandler($this->storedEventRepository, $this->domainEventRegistry);
    }

    /**
     * @test
     */
    public function testItShouldReturnZeroIfNoEvents(): void
    {
        $accountId = AccountIdMother::create()->value();
        $query = new GetAccountBalanceQuery($accountId, new DateTimeImmutable());

        $this->storedEventRepository
            ->expects($this->once())
            ->method('findByEventTypesAndOccurredBetweenAndAggregateId')
            ->willReturn([]);

        $this->storedEventRepository
            ->expects($this->once())
            ->method('findByEventTypesAndOccurredBetween')
            ->willReturn([]);

        $result = ($this->handler)($query);

        $this->assertEquals(0, $result);
    }

    /**
     * @test
     */
    public function testItShouldCalculateBalanceWithOnlyTransactions(): void
    {
        $accountId = AccountIdMother::create()->value();
        $otherAccountId = AccountIdMother::create()->value();
        $query = new GetAccountBalanceQuery($accountId, new DateTimeImmutable('2025-12-31'));

        // 1. Mock no initial balance
        $this->storedEventRepository
            ->expects($this->once())
            ->method('findByEventTypesAndOccurredBetweenAndAggregateId')
            ->willReturn([]);

        // 2. Mock transaction events for multiple accounts
        $this->storedEventRepository
            ->expects($this->once())
            ->method('findByEventTypesAndOccurredBetween')
            ->willReturn([
                $this->createStoredEvent(IncomeWasEntered::class, $accountId, 1000, '2025-01-10'),
                $this->createStoredEvent(ExpenseWasEntered::class, $accountId, 300, '2025-01-15'),
                $this->createStoredEvent(IncomeWasEntered::class, $otherAccountId, 5000, '2025-01-20'), // Should be ignored
            ]);

        $result = ($this->handler)($query);

        // 1000 (income) - 300 (expense) = 700
        $this->assertEquals(700, $result);
    }

    /**
     * @test
     */
    public function testItShouldCalculateBalanceWithInitialBalanceAndTransactions(): void
    {
        $accountId = AccountIdMother::create()->value();
        $query = new GetAccountBalanceQuery($accountId, new DateTimeImmutable('2025-12-31'));

        // 1. Mock initial balance
        $initialBalanceEvent = $this->createStoredEvent(InitialBalanceSet::class, $accountId, 5000, '2025-01-01');
        $this->storedEventRepository
            ->expects($this->once())
            ->method('findByEventTypesAndOccurredBetweenAndAggregateId')
            ->willReturn([$initialBalanceEvent]);

        // 2. Mock transaction events
        $this->storedEventRepository
            ->expects($this->once())
            ->method('findByEventTypesAndOccurredBetween')
            ->willReturn([
                $this->createStoredEvent(IncomeWasEntered::class, $accountId, 1000, '2025-02-01'),
                $this->createStoredEvent(ExpenseWasEntered::class, $accountId, 500, '2025-03-01'),
            ]);

        $result = ($this->handler)($query);

        // 5000 (initial) + 1000 (income) - 500 (expense) = 5500
        $this->assertEquals(5500, $result);
    }

    /**
     * @test
     */
    public function testItShouldIgnoreTransactionsAfterUpToDate(): void
    {
        $accountId = AccountIdMother::create()->value();
        $upToDate = new DateTimeImmutable('2025-01-31');
        $query = new GetAccountBalanceQuery($accountId, $upToDate);

        $this->storedEventRepository
            ->expects($this->once())
            ->method('findByEventTypesAndOccurredBetweenAndAggregateId')
            ->willReturn([]);

        $this->storedEventRepository
            ->expects($this->once())
            ->method('findByEventTypesAndOccurredBetween')
            ->willReturn([
                $this->createStoredEvent(IncomeWasEntered::class, $accountId, 1000, '2025-01-10'),
                $this->createStoredEvent(ExpenseWasEntered::class, $accountId, 200, '2025-02-05'), // Should be ignored
            ]);

        $result = ($this->handler)($query);

        $this->assertEquals(1000, $result);
    }

    /**
     * @test
     */
    public function testAccountBalanceIncludesCompensatedExpenseEvents(): void
    {
        $accountId = AccountIdMother::create()->value();
        $expenseId = Uuid::random()->value();
        $query = new GetAccountBalanceQuery($accountId, new DateTimeImmutable('2025-12-31'));

        $initialBalanceEvent = $this->createStoredEvent(InitialBalanceSet::class, $accountId, 100000, '2025-01-01');
        $this->storedEventRepository
            ->expects($this->once())
            ->method('findByEventTypesAndOccurredBetweenAndAggregateId')
            ->willReturn([$initialBalanceEvent]);

        // Compensation amount is negative by design (ExpenseWasCompensated stores -amount)
        $this->storedEventRepository
            ->expects($this->once())
            ->method('findByEventTypesAndOccurredBetween')
            ->willReturn([
                $this->createStoredEvent(ExpenseWasEntered::class, $accountId, 30000, '2025-02-01'),
                $this->createStoredEventCompensated($expenseId, $accountId, -30000, '2025-02-15'),
                $this->createStoredEvent(ExpenseWasEntered::class, $accountId, 20000, '2025-02-20'),
            ]);

        $result = ($this->handler)($query);

        // 100000 (initial) - 30000 (expense) + 30000 (compensation reverses expense) - 20000 (new expense) = 80000
        $this->assertEquals(80000, $result);
    }

    /**
     * @test
     */
    public function testExpenseUpdateAmountRecordsExpenseWasEnteredWhenAccountIsPresent(): void
    {
        $expense = ExpenseMother::create(amount: 30000);
        $expense->compensate();
        $expense->updateAmount(20000);

        $events = $expense->pullDomainEvents();

        $this->assertCount(2, $events);
        $this->assertInstanceOf(ExpenseWasCompensated::class, $events[0]);
        $this->assertInstanceOf(ExpenseWasEntered::class, $events[1]);
        $this->assertEquals(20000, $events[1]->toPrimitives()['amount']);
    }

    /**
     * @test
     */
    public function testItShouldIncludeCompensatedExpenseInBalance(): void
    {
        $accountId = AccountIdMother::create()->value();
        $expenseId = Uuid::random()->value();
        $query = new GetAccountBalanceQuery($accountId, new DateTimeImmutable('2025-12-31'));

        // 1. No initial balance
        $this->storedEventRepository
            ->expects($this->once())
            ->method('findByEventTypesAndOccurredBetweenAndAggregateId')
            ->willReturn([]);

        // 2. Expense entered (500) then compensated (-500) → net effect: 0
        $this->storedEventRepository
            ->expects($this->once())
            ->method('findByEventTypesAndOccurredBetween')
            ->willReturn([
                $this->createStoredEvent(ExpenseWasEntered::class, $accountId, 500, '2025-01-10'),
                $this->createStoredEventCompensated($expenseId, $accountId, -500, '2025-06-15'),
            ]);

        $result = ($this->handler)($query);

        // 500 (expense) + (-500) (compensation) = 0
        $this->assertEquals(0, $result);
    }

    /**
     * @test
     */
    public function testItShouldIgnoreCompensatedExpenseAfterUpToDate(): void
    {
        $accountId = AccountIdMother::create()->value();
        $expenseId = Uuid::random()->value();
        $upToDate = new DateTimeImmutable('2025-03-31');
        $query = new GetAccountBalanceQuery($accountId, $upToDate);

        $this->storedEventRepository
            ->expects($this->once())
            ->method('findByEventTypesAndOccurredBetweenAndAggregateId')
            ->willReturn([]);

        // Expense entered before upToDate, compensation after → only expense counts
        $this->storedEventRepository
            ->expects($this->once())
            ->method('findByEventTypesAndOccurredBetween')
            ->willReturn([
                $this->createStoredEvent(ExpenseWasEntered::class, $accountId, 300, '2025-01-10'),
                $this->createStoredEventCompensated($expenseId, $accountId, -300, '2025-06-15'), // After upToDate
            ]);

        $result = ($this->handler)($query);

        // Only expense counts: -300
        $this->assertEquals(-300, $result);
    }

    /**
     * Helper to create a mock StoredEvent containing a real DomainEvent.
     */
    private function createStoredEvent(string $eventClass, string $accountId, int $amount, string $date): StoredEvent
    {
        $domainEvent = null;

        if ($eventClass === InitialBalanceSet::class) {
            $domainEvent = new InitialBalanceSet($accountId, $amount, $date);
        } elseif ($eventClass === ExpenseWasEntered::class) {
            $domainEvent = new ExpenseWasEntered(Uuid::random()->value(), $amount, Uuid::random()->value(), $accountId, $date, 'Test Expense');
        } elseif ($eventClass === IncomeWasEntered::class) {
            $domainEvent = new IncomeWasEntered(Uuid::random()->value(), $amount, Uuid::random()->value(), Uuid::random()->value(), $accountId, $date, 'Test Income');
        }

        if ($domainEvent === null) {
            $this->fail("Unsupported event class in test helper: {$eventClass}");
        }

        $storedEvent = $this->createMock(StoredEvent::class);
        $storedEvent->method('toDomainEvent')->willReturn($domainEvent);

        return $storedEvent;
    }

    /**
     * Helper to create a mock StoredEvent for ExpenseWasCompensated.
     */
    private function createStoredEventCompensated(string $expenseId, string $accountId, int $amount, string $date): StoredEvent
    {
        $domainEvent = new ExpenseWasCompensated(
            aggregateId: $expenseId,
            amount: $amount,
            type: Uuid::random()->value(),
            accountId: $accountId,
            dueDate: $date,
        );

        $storedEvent = $this->createMock(StoredEvent::class);
        $storedEvent->method('toDomainEvent')->willReturn($domainEvent);

        return $storedEvent;
    }
}
