<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\FindExpense;

use App\Context\Expense\Domain\ExpenseRepository;
use App\Shared\Application\QueryHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
readonly class FindExpenseQueryHandler implements QueryHandler
{
    public function __construct(private ExpenseRepository $repository) {}

    public function __invoke(FindExpenseQuery $query): array
    {
        $expenseId = $query->id();
        $expense = $this->repository->findOneByIdOrFail($expenseId);

        return $expense->toArray();
    }
}
