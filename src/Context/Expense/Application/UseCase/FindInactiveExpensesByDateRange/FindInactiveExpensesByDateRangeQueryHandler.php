<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\FindInactiveExpensesByDateRange;

use App\Context\Expense\Domain\ExpenseRepository;
use App\Shared\Application\QueryHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class FindInactiveExpensesByDateRangeQueryHandler implements QueryHandler
{
    public function __construct(private ExpenseRepository $repository) {}

    public function __invoke(FindInactiveExpensesByDateRangeQuery $query): array
    {
        $expenses = $this->repository->findInactiveByDateRange($query->dateRange());

        return array_map(fn($expense) => $expense->toArray(), $expenses);
    }
}
