<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\FindAllExpenses;

use App\Context\Expense\Application\UseCase\FindAllExpenses\FindAllExpensesQuery;
use App\Context\Expense\Application\UseCase\FindAllExpenses\FindAllExpensesQueryHandler;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use PHPUnit\Framework\TestCase;

class FindAllExpensesTest extends TestCase
{
    private FindAllExpensesQueryHandler $handler;
    private ExpenseRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ExpenseRepository::class);
        $this->handler = new FindAllExpensesQueryHandler($this->repository);
    }

    public function test_it_should_find_all_expenses(): void
    {
        $expense1 = ExpenseMother::create();
        $expense2 = ExpenseMother::create();
        $expenses = [$expense1, $expense2];

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($expenses);

        $query = new FindAllExpensesQuery();
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('expenses', $result);
        $this->assertArrayHasKey('qtd', $result);
        $this->assertCount(2, $result['expenses']);
        $this->assertSame(2, $result['qtd']);
        $this->assertEquals($expense1->toArray(), $result['expenses'][0]);
        $this->assertEquals($expense2->toArray(), $result['expenses'][1]);
    }

    public function test_it_should_return_empty_array_if_no_expenses_found(): void
    {
        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

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

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($expenses);

        $query = new FindAllExpensesQuery();
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('expenses', $result);
        $this->assertArrayHasKey('qtd', $result);
        $this->assertCount(1, $result['expenses']);
        $this->assertSame(1, $result['qtd']);
        $this->assertEquals($expense->toArray(), $result['expenses'][0]);
    }
}
