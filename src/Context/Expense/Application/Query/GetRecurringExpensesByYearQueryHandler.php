<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\Query;

use App\Context\Expense\Application\GetRecurringExpensesByYearUseCase;
use App\Context\Expense\Domain\RecurringExpense;

class GetRecurringExpensesByYearQueryHandler
{
    private GetRecurringExpensesByYearUseCase $getRecurringExpensesByYearUseCase;

    public function __construct(GetRecurringExpensesByYearUseCase $getRecurringExpensesByYearUseCase)
    {
        $this->getRecurringExpensesByYearUseCase = $getRecurringExpensesByYearUseCase;
    }

    /**
     * @return RecurringExpense[]
     */
    public function __invoke(GetRecurringExpensesByYearQuery $query): array
    {
        return ($this->getRecurringExpensesByYearUseCase)($query->year());
    }
}
