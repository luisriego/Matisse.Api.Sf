<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

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
        $this->queryBuilder->method('expr')->willReturn(new \Doctrine\ORM\Query\Expr());


        // Create a partial mock of the repository. We want to test its PHP logic,
        // but we need to control the part that talks to the database (createQueryBuilder).
        $this->repository = $this->getMockBuilder(DoctrineRecurringExpenseRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $this->repository->method('createQueryBuilder')->with('re')->willReturn($this->queryBuilder);
    }

    /**
     * @test
     * @throws \DateMalformedStringException
     */
    public function test_it_filters_active_for_date_range_correctly(): void
    {
        // Arrange
        $currentYear = (int) (new DateTime())->format('Y');
        $testYear = $currentYear > 2025 ? $currentYear + 1 : 2025;

        $dateRange = DateRange::fromMonth($testYear, 7);

        $expenseInJuly = RecurringExpenseMother::create(
            dueDay: new ExpenseDueDay(15),
            monthsOfYear: [7],
            startDate: new ExpenseStartDate((new DateTime())->modify('+1 day'))
        );

        $expenseEveryMonth = RecurringExpenseMother::create(
            dueDay: new ExpenseDueDay(20),
            monthsOfYear: null,
            startDate: new ExpenseStartDate((new DateTime())->modify('+1 day'))
        );

        // Mock the query to return the expected filtered data
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([
            $expenseInJuly,
            $expenseEveryMonth,
        ]);

        $this->queryBuilder->method('getQuery')->willReturn($query);

        // Act
        $result = $this->repository->findActiveForDateRange($dateRange);

        // Assert
        $this->assertCount(2, $result, 'Should find exactly 2 matching expenses');
        $this->assertContains($expenseInJuly, $result);
        $this->assertContains($expenseEveryMonth, $result);
    }
}
