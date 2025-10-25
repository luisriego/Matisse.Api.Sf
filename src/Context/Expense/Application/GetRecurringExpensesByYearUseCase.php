<?php

declare(strict_types=1);

namespace App\Context\Expense\Application;

use App\Context\Expense\Domain\RecurringExpenseRepository;

class GetRecurringExpensesByYearUseCase
{
    private RecurringExpenseRepository $recurringExpenseRepository;

    public function __construct(RecurringExpenseRepository $recurringExpenseRepository)
    {
        $this->recurringExpenseRepository = $recurringExpenseRepository;
    }

    public function __invoke(int $year): array
    {
        return $this->recurringExpenseRepository->findByYear($year);
    }
}
