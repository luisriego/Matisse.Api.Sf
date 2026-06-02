<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Application\UseCase\FindExpense;

use App\Context\Expense\Application\UseCase\FindExpense\FindExpenseQuery;
use App\Context\Expense\Application\UseCase\FindExpense\FindExpenseQueryHandler;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class FindExpenseTest extends TestCase
{
    private FindExpenseQueryHandler $handler;
    private ExpenseRepository|MockInterface $repository;
    private MockInterface|SerializerInterface $serializer;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(ExpenseRepository::class);
        $this->serializer = Mockery::mock(SerializerInterface::class);
        $this->handler = new FindExpenseQueryHandler($this->repository, $this->serializer);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testItShouldFindAnExpense(): void
    {
        $expense = ExpenseMother::create();
        $query = new FindExpenseQuery($expense->id());
        $expenseArray = ['id' => $expense->id()];

        $this->repository->shouldReceive('findOneByIdOrFail')
            ->once()
            ->with($expense->id())
            ->andReturn($expense);

        $this->serializer->shouldReceive('normalize')
            ->once()
            ->with($expense)
            ->andReturn($expenseArray);

        $result = ($this->handler)($query);

        $this->assertEquals($expenseArray, $result);
    }
}
