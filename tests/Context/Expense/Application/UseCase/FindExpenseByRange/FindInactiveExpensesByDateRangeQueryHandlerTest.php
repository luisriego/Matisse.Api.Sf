<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\FindExpenseByRange;

use App\Context\Expense\Application\UseCase\FindInactiveExpensesByDateRange\FindInactiveExpensesByDateRangeQuery;
use App\Context\Expense\Application\UseCase\FindInactiveExpensesByDateRange\FindInactiveExpensesByDateRangeQueryHandler;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDate;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Shared\Domain\DateRangeMother;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class FindInactiveExpensesByDateRangeQueryHandlerTest extends TestCase
{
    private ExpenseRepository|MockInterface $repository;
    private FindInactiveExpensesByDateRangeQueryHandler $handler;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(ExpenseRepository::class);
        $this->handler = new FindInactiveExpensesByDateRangeQueryHandler($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testFindInactiveExpensesByDateRange(): void
    {
        // Arrange
        $dateRange = DateRangeMother::create();
        $inactiveExpense1 = ExpenseMother::createInactive();
        $inactiveExpense2 = ExpenseMother::createInactive();
        $inactiveExpenses = [$inactiveExpense1, $inactiveExpense2];

        $this->repository
            ->shouldReceive('findInactiveByDateRange')
            ->once()
            ->with($dateRange)
            ->andReturn($inactiveExpenses);

        $query = new FindInactiveExpensesByDateRangeQuery($dateRange);

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals($inactiveExpense1->toArray(), $result[0]);
        $this->assertEquals($inactiveExpense2->toArray(), $result[1]);
    }

    public function testFindInactiveExpensesByDateRangeEmptyResult(): void
    {
        // Arrange
        $dateRange = DateRangeMother::create();

        $this->repository
            ->shouldReceive('findInactiveByDateRange')
            ->once()
            ->with($dateRange)
            ->andReturn([]);

        $query = new FindInactiveExpensesByDateRangeQuery($dateRange);

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function testFindInactiveExpensesByDateRangeWithSpecificMonth(): void
    {
        // Arrange
        $dateRange = DateRangeMother::fromMonth(2025, 8);
        $inactiveExpense = ExpenseMother::createInactive(
            null, null, null,
            new ExpenseDueDate(new \DateTime("2025-07-15"))
        );

        $inactiveExpenses = [$inactiveExpense];

        $this->repository
            ->shouldReceive('findInactiveByDateRange')
            ->once()
            ->with($dateRange)
            ->andReturn($inactiveExpenses);

        $query = new FindInactiveExpensesByDateRangeQuery($dateRange);

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals($inactiveExpense->toArray(), $result[0]);
    }

    public function testFindInactiveExpensesByDateRangeCallsRepositoryOnce(): void
    {
        // Arrange
        $dateRange = DateRangeMother::create();
        $inactiveExpenses = [ExpenseMother::createInactive()];

        $this->repository
            ->shouldReceive('findInactiveByDateRange')
            ->once()
            ->with($dateRange)
            ->andReturn($inactiveExpenses);

        $query = new FindInactiveExpensesByDateRangeQuery($dateRange);

        // Act
        ($this->handler)($query);

        // Assert - Mockery will verify the expectations in tearDown()
        $this->assertTrue(true);
    }
}
