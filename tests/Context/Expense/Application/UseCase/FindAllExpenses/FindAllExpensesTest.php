<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\FindAllExpenses;

use App\Context\Expense\Application\UseCase\FindAllExpenses\FindAllExpensesQuery;
use App\Context\Expense\Application\UseCase\FindAllExpenses\FindAllExpensesQueryHandler;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class FindAllExpensesTest extends TestCase
{
    private FindAllExpensesQueryHandler $handler;
    private ExpenseRepository|MockInterface $repository;
    private SerializerInterface|MockInterface $serializer;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(ExpenseRepository::class);
        $this->serializer = Mockery::mock(SerializerInterface::class);
        $this->handler = new FindAllExpensesQueryHandler($this->repository, $this->serializer);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_it_should_find_all_expenses(): void
    {
        $expense1 = ExpenseMother::create();
        $expense2 = ExpenseMother::create();
        $expenses = [$expense1, $expense2];
        $expensesArray = [
            ['id' => $expense1->id()],
            ['id' => $expense2->id()],
        ];

        $this->repository->shouldReceive('findAll')
            ->once()
            ->andReturn($expenses);

        $this->serializer->shouldReceive('normalize')
            ->once()
            ->with($expenses)
            ->andReturn($expensesArray);

        $query = new FindAllExpensesQuery();
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('expenses', $result);
        $this->assertArrayHasKey('qtd', $result);
        $this->assertCount(2, $result['expenses']);
        $this->assertSame(2, $result['qtd']);
        $this->assertEquals($expensesArray, $result['expenses']);
    }

    public function test_it_should_return_empty_array_if_no_expenses_found(): void
    {
        $this->repository->shouldReceive('findAll')
            ->once()
            ->andReturn([]);

        $this->serializer->shouldReceive('normalize')
            ->once()
            ->with([])
            ->andReturn([]);

        $query = new FindAllExpensesQuery();
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('expenses', $result);
        $this->assertArrayHasKey('qtd', $result);
        $this->assertCount(0, $result['expenses']);
        $this->assertSame(0, $result['qtd']);
    }

    public function test_it_should_find_a_single_expense(): void
    {
        $expense = ExpenseMother::create();
        $expenses = [$expense];
        $expenseArray = [['id' => $expense->id()]];

        $this->repository->shouldReceive('findAll')
            ->once()
            ->andReturn($expenses);

        $this->serializer->shouldReceive('normalize')
            ->once()
            ->with($expenses)
            ->andReturn($expenseArray);

        $query = new FindAllExpensesQuery();
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('expenses', $result);
        $this->assertArrayHasKey('qtd', $result);
        $this->assertCount(1, $result['expenses']);
        $this->assertSame(1, $result['qtd']);
        $this->assertEquals($expenseArray, $result['expenses']);
    }
}
