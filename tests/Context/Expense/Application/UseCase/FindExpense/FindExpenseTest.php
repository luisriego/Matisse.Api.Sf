<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\FindExpense;

use App\Context\Expense\Application\UseCase\FindExpense\FindExpenseQuery;
use App\Context\Expense\Application\UseCase\FindExpense\FindExpenseQueryHandler;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use PHPUnit\Framework\TestCase;

class FindExpenseTest extends TestCase
{
    private FindExpenseQueryHandler $handler;
    private ExpenseRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ExpenseRepository::class);
        $this->handler = new FindExpenseQueryHandler($this->repository);
    }

    public function test_it_should_find_an_expense(): void
    {
        $expense = ExpenseMother::create();
        $query = new FindExpenseQuery($expense->id());

        $this->repository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with(new ExpenseId($expense->id()))
            ->willReturn($expense);

        $result = ($this->handler)($query);

        $this->assertEquals($expense->toArray(), $result);
    }
}
