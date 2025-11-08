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
use Symfony\Component\Serializer\SerializerInterface;

class FindActiveExpensesByDateRangeQueryHandlerTest extends TestCase
{
    private ExpenseRepository|MockInterface $repository;
    private SerializerInterface|MockInterface $serializer;
    private FindActiveExpensesByDateRangeQueryHandler $handler;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(ExpenseRepository::class);
        $this->serializer = Mockery::mock(SerializerInterface::class);
        $this->handler = new FindActiveExpensesByDateRangeQueryHandler($this->repository, $this->serializer);
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
        $activeExpensesArray = [
            ['id' => $activeExpense1->id()],
            ['id' => $activeExpense2->id()],
        ];

        $this->repository
            ->shouldReceive('findActiveByDateRange')
            ->once()
            ->with($dateRange)
            ->andReturn($activeExpenses);

        $this->serializer
            ->shouldReceive('normalize')
            ->once()
            ->with($activeExpenses)
            ->andReturn($activeExpensesArray);

        $query = new FindActiveExpensesByDateRangeQuery($dateRange);

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals($activeExpensesArray, $result);
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

        $this->serializer
            ->shouldReceive('normalize')
            ->once()
            ->with([])
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
        $activeExpenseArray = [['id' => $activeExpense->id()]];

        $this->repository
            ->shouldReceive('findActiveByDateRange')
            ->once()
            ->with($dateRange)
            ->andReturn($activeExpenses);

        $this->serializer
            ->shouldReceive('normalize')
            ->once()
            ->with($activeExpenses)
            ->andReturn($activeExpenseArray);

        $query = new FindActiveExpensesByDateRangeQuery($dateRange);

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals($activeExpenseArray, $result);
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

        $this->serializer
            ->shouldReceive('normalize')
            ->once()
            ->andReturn([]);

        $query = new FindActiveExpensesByDateRangeQuery($dateRange);

        // Act
        ($this->handler)($query);

        // Assert - Mockery will verify the expectations in tearDown()
        $this->assertTrue(true);
    }
}
