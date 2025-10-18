<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\GetPendingMonthlyRecurringExpenses;

use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Shared\Application\QueryHandler;
use DateMalformedStringException;
use DateTimeImmutable;

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

        // 1. Get all recurring expenses that are not predefined amount
        $allRecurringExpenses = $this->recurringExpenseRepository->findByHasPredefinedAmount(false);

        foreach ($allRecurringExpenses as $recurringExpense) {
            // Filter by account if provided
            if (null !== $accountId && $recurringExpense->accountId() !== $accountId) {
                continue;
            }

            // Check if this recurring expense is active for the given month/year
            $targetDate = new DateTimeImmutable(sprintf('%d-%02d-%02d', $year, $month, $recurringExpense->dueDay()));

            if ($targetDate < $recurringExpense->startDate() || $targetDate > $recurringExpense->endDate()) { // Corrected: Removed ->value()
                continue;
            }

            // Check if an actual expense has already been entered for this month/year
            $existingExpense = $this->expenseRepository->findByRecurringExpenseAndMonthYear(
                $recurringExpense->id(), // Corrected: Removed ->value()
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
