<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDay;
use App\Context\Expense\Domain\ValueObject\ExpenseEndDate;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Context\Expense\Domain\ValueObject\ExpenseStartDate;

final class RecurringExpenseMother
{
    /**
     * @throws \DateMalformedStringException
     */
    public static function create(
        ?ExpenseId $id = null,
        ?ExpenseAmount $amount = null,
        ?ExpenseType $expenseType = null,
        ?ExpenseDueDay $dueDay = null,
        ?array $monthsOfYear = null,
        ?ExpenseStartDate $startDate = null,
        ?ExpenseEndDate $endDate = null,
        ?string $description = null
    ): RecurringExpense {
        $id            = $id            ?? ExpenseIdMother::create();
        $amount        = $amount        ?? ExpenseAmountMother::create();
        $expenseType   = $expenseType   ?? ExpenseTypeMother::create();
        $dueDay        = $dueDay        ?? new ExpenseDueDay(15);
        $monthsOfYear  = $monthsOfYear  ?? [1, 4, 7, 10];
        $startDate     = $startDate     ?? new ExpenseStartDate(new \DateTime("2025-01-01 12:00:00"));
        $endDate       = $endDate       ?? new ExpenseEndDate(new \DateTime("2025-12-31 12:00:00"));
        $description   = $description   ?? 'Default recurring description';

        return RecurringExpense::create(
            $id,
            $amount,
            $expenseType,
            $dueDay,
            $monthsOfYear,
            $startDate,
            $endDate,
            $description
        );
    }
}

