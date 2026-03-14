<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\Query;

use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Shared\Application\QueryHandler;

final readonly class GetRecurringExpensesByYearQueryHandler implements QueryHandler
{
    public function __construct(
        private RecurringExpenseRepository $recurringExpenseRepository,
    ) {}

    /**
     * @return RecurringExpense[]
     */
    public function __invoke(GetRecurringExpensesByYearQuery $query): array
    {
        return $this->recurringExpenseRepository->findByYear($query->year());
    }
}
