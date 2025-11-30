<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\GetPendingMonthlyRecurringExpenses;

use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Shared\Application\QueryHandler;
use DateMalformedStringException;
use DateTimeImmutable;

use function in_array;
use function sprintf;

final readonly class GetPendingMonthlyRecurringExpensesQueryHandler implements QueryHandler
{
    public function __construct(
        private RecurringExpenseRepository $recurringExpenseRepository,
        private ExpenseRepository $expenseRepository,
    ) {}

    /**
     * @return RecurringExpense[]
     *
     * @throws DateMalformedStringException
     */
    public function __invoke(GetPendingMonthlyRecurringExpensesQuery $query): array
    {
        $month = $query->month();
        $year = $query->year();
        $accountId = $query->accountId();

        $pendingRecurringExpenses = [];

        // 1. Get all recurring expenses
        $allRecurringExpenses = $this->recurringExpenseRepository->findAll();

        foreach ($allRecurringExpenses as $recurringExpense) {
            // Filter by account if provided
            if (null !== $accountId && $recurringExpense->accountId() !== $accountId) {
                continue;
            }

            // Check if the recurring expense is configured for the given month
            if (!in_array($month, $recurringExpense->monthsOfYear(), true)) {
                continue;
            }

            // Check if this recurring expense is active for the given month/year
            $targetDate = new DateTimeImmutable(sprintf('%d-%02d-%02d', $year, $month, $recurringExpense->dueDay()));

            if ($targetDate < $recurringExpense->startDate() || ($recurringExpense->endDate() && $targetDate > $recurringExpense->endDate())) {
                continue;
            }

            // Check if an actual expense has already been entered for this month/year
            $existingExpense = $this->expenseRepository->findByRecurringExpenseAndMonthYear(
                $recurringExpense->id(),
                $month,
                $year,
            );

            if (null === $existingExpense) {
                $pendingRecurringExpenses[] = $recurringExpense;
            }
        }

        return $pendingRecurringExpenses;
    }
}
