<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDay;
use App\Context\Expense\Domain\ValueObject\ExpenseStartDate;
use App\Context\Expense\Infrastructure\Persistence\Doctrine\DoctrineRecurringExpenseRepository;
use App\Shared\Domain\ValueObject\DateRange;
use DateTime;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RecurringExpenseRepositoryTest extends TestCase
{
    private DoctrineRecurringExpenseRepository&MockObject $repository;
    private QueryBuilder&MockObject $queryBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        // Chain all the builder methods to return the mock itself
        $this->queryBuilder->method('where')->willReturnSelf();
        $this->queryBuilder->method('andWhere')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
        $this->queryBuilder->method('orderBy')->willReturnSelf();

        // Create a partial mock of the repository. We want to test its PHP logic,
        // but we need to control the part that talks to the database (createQueryBuilder).
        $this->repository = $this->getMockBuilder(DoctrineRecurringExpenseRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $this->repository->method('createQueryBuilder')->with('r')->willReturn($this->queryBuilder);
    }

    /**
     * @test
     * @throws \DateMalformedStringException
     */
    public function test_it_filters_active_for_date_range_correctly(): void
    {
        // Arrange
        $dateRange = DateRange::fromMonth(2025, 7); // July 2025

        // This expense should be returned: applies to July, due day is valid.
        $expenseInJuly = RecurringExpenseMother::create(
            dueDay: new ExpenseDueDay(15),
            monthsOfYear: [7],
            startDate: new ExpenseStartDate(new DateTime('2025-01-01'))
        );

        // This expense should NOT be returned: applies to August.
        $expenseInAugust = RecurringExpenseMother::create(
            dueDay: new ExpenseDueDay(15),
            monthsOfYear: [8],
            startDate: new ExpenseStartDate(new DateTime('2025-01-01'))
        );

        // This expense should be returned: applies to all months (null).
        $expenseEveryMonth = RecurringExpenseMother::create(
            dueDay: new ExpenseDueDay(20),
            monthsOfYear: null,
            startDate: new ExpenseStartDate(new DateTime('2025-01-01'))
        );

        // This expense should NOT be returned: its due day (32) is > days in July (31).
        // We mock it because ExpenseDueDay(32) would throw an exception, which is correct domain behavior.
        // This allows us to test the repository's filtering logic in isolation.
        $expenseInvalidDay = $this->createMock(RecurringExpense::class);
        $expenseInvalidDay->method('monthsOfYear')->willReturn([7]);
        $expenseInvalidDay->method('dueDay')->willReturn(32);

        // Mock the query to return our test data
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([
            $expenseInJuly,
            $expenseInAugust,
            $expenseEveryMonth,
            $expenseInvalidDay,
        ]);

        $this->queryBuilder->method('getQuery')->willReturn($query);

        // Act
        $result = $this->repository->findActiveForDateRange($dateRange);

        // Assert
        $this->assertCount(2, $result, 'Should find exactly 2 matching expenses');
        $this->assertContains($expenseInJuly, $result);
        $this->assertContains($expenseEveryMonth, $result);
        $this->assertNotContains($expenseInAugust, $result);
        $this->assertNotContains($expenseInvalidDay, $result);
    }
}