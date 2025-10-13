<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\FindExpenseByRange;

use App\Context\Expense\Application\UseCase\FindActiveExpensesByDateRange\FindActiveExpensesByDateRangeQuery;
use App\Context\Expense\Application\UseCase\FindActiveExpensesByDateRange\FindActiveExpensesByDateRangeQueryHandler;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDate;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Shared\Domain\DateRangeMother;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class FindActiveExpensesByDateRangeQueryHandlerTest extends TestCase
{
    private ExpenseRepository|MockInterface $repository;
    private FindActiveExpensesByDateRangeQueryHandler $handler;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(ExpenseRepository::class);
        $this->handler = new FindActiveExpensesByDateRangeQueryHandler($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testFindActiveExpensesByDateRange(): void
    {
        // Arrange
        $dateRange = DateRangeMother::create();
        $activeExpense1 = ExpenseMother::create();
        $activeExpense2 = ExpenseMother::create();
        $activeExpenses = [$activeExpense1, $activeExpense2];

        $this->repository
            ->shouldReceive('findActiveByDateRange')
            ->once()
            ->with($dateRange)
            ->andReturn($activeExpenses);

        $query = new FindActiveExpensesByDateRangeQuery($dateRange);

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals($activeExpense1->toArray(), $result[0]);
        $this->assertEquals($activeExpense2->toArray(), $result[1]);
    }

    public function testFindActiveExpensesByDateRangeEmptyResult(): void
    {
        // Arrange
        $dateRange = DateRangeMother::create();

        $this->repository
            ->shouldReceive('findActiveByDateRange')
            ->once()
            ->with($dateRange)
            ->andReturn([]);

        $query = new FindActiveExpensesByDateRangeQuery($dateRange);

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function testFindActiveExpensesByDateRangeWithSpecificMonth(): void
    {
        // Arrange
        $dateRange = DateRangeMother::fromMonth(2025, 7);
        $activeExpense = ExpenseMother::create(
            null, null, null,
            new ExpenseDueDate(new \DateTime("2025-07-15"))
        );

        $activeExpenses = [$activeExpense];

        $this->repository
            ->shouldReceive('findActiveByDateRange')
            ->once()
            ->with($dateRange)
            ->andReturn($activeExpenses);

        $query = new FindActiveExpensesByDateRangeQuery($dateRange);

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals($activeExpense->toArray(), $result[0]);
    }

    public function testFindActiveExpensesByDateRangeCallsRepositoryOnce(): void
    {
        // Arrange
        $dateRange = DateRangeMother::create();
        $activeExpenses = [ExpenseMother::create()];

        $this->repository
            ->shouldReceive('findActiveByDateRange')
            ->once()
            ->with($dateRange)
            ->andReturn($activeExpenses);

        $query = new FindActiveExpensesByDateRangeQuery($dateRange);

        // Act
        ($this->handler)($query);

        // Assert - Mockery will verify the expectations in tearDown()
        $this->assertTrue(true);
    }
}
