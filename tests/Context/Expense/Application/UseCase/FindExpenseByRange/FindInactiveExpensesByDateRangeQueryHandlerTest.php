<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\FindExpenseByRange;

use App\Context\Expense\Application\UseCase\FindInactiveExpensesByDateRange\FindInactiveExpensesByDateRangeQuery;
use App\Context\Expense\Application\UseCase\FindInactiveExpensesByDateRange\FindInactiveExpensesByDateRangeQueryHandler;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Shared\Domain\DateRangeMother;
use DateMalformedStringException;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class FindInactiveExpensesByDateRangeQueryHandlerTest extends TestCase
{
    private ExpenseRepository|MockInterface $repository;
    private MockInterface|SerializerInterface $serializer;
    private FindInactiveExpensesByDateRangeQueryHandler $handler;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(ExpenseRepository::class);
        $this->serializer = Mockery::mock(SerializerInterface::class);
        $this->handler = new FindInactiveExpensesByDateRangeQueryHandler($this->repository, $this->serializer);
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
        $inactiveExpensesArray = [
            ['id' => $inactiveExpense1->id()],
            ['id' => $inactiveExpense2->id()],
        ];

        $this->repository
            ->shouldReceive('findInactiveByDateRange')
            ->once()
            ->with($dateRange)
            ->andReturn($inactiveExpenses);

        $this->serializer
            ->shouldReceive('normalize')
            ->once()
            ->with($inactiveExpenses)
            ->andReturn($inactiveExpensesArray);

        $query = new FindInactiveExpensesByDateRangeQuery($dateRange);

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals($inactiveExpensesArray, $result);
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

        $this->serializer
            ->shouldReceive('normalize')
            ->once()
            ->with([])
            ->andReturn([]);

        $query = new FindInactiveExpensesByDateRangeQuery($dateRange);

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testFindInactiveExpensesByDateRangeWithSpecificMonth(): void
    {
        // Arrange
        $dateRange = DateRangeMother::fromMonth(2025, 8);
        $inactiveExpense = ExpenseMother::createInactive(
            dueDate: new DateTime('2025-07-15'),
        );
        $inactiveExpenses = [$inactiveExpense];
        $inactiveExpenseArray = [['id' => $inactiveExpense->id()]];

        $this->repository
            ->shouldReceive('findInactiveByDateRange')
            ->once()
            ->with($dateRange)
            ->andReturn($inactiveExpenses);

        $this->serializer
            ->shouldReceive('normalize')
            ->once()
            ->with($inactiveExpenses)
            ->andReturn($inactiveExpenseArray);

        $query = new FindInactiveExpensesByDateRangeQuery($dateRange);

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals($inactiveExpenseArray, $result);
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

        $this->serializer
            ->shouldReceive('normalize')
            ->once()
            ->andReturn([]);

        $query = new FindInactiveExpensesByDateRangeQuery($dateRange);

        // Act
        ($this->handler)($query);

        // Assert - Mockery will verify the expectations in tearDown()
        $this->assertTrue(true);
    }
}
