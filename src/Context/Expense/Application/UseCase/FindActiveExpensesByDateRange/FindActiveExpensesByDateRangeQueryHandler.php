<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\FindActiveExpensesByDateRange;

use App\Context\Expense\Domain\ExpenseRepository;
use App\Shared\Application\QueryHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use function array_map;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class FindActiveExpensesByDateRangeQueryHandler implements QueryHandler
{
    public function __construct(private ExpenseRepository $repository) {}

    public function __invoke(FindActiveExpensesByDateRangeQuery $query): array
    {
        $expenses = $this->repository->findActiveByDateRange($query->dateRange());

        return array_map(fn ($expense) => $expense->toArray(), $expenses);
    }
}
