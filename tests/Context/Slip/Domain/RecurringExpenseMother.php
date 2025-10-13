<?php

namespace App\Tests\Context\Slip\Domain;

use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseEndDate;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDay;
use App\Context\Expense\Domain\ValueObject\ExpenseStartDate;
use App\Tests\Context\Expense\Domain\ExpenseAmountMother;
use App\Tests\Context\Expense\Domain\ExpenseIdMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use DateTimeInterface;

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
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        ?string $description = null
    ): RecurringExpense {
        $idVal          = $id ?? ExpenseIdMother::create();
        $amountVal      = $amount ?? ExpenseAmountMother::create();
        $typeVal        = $expenseType ?? ExpenseTypeMother::create();
        $due            = $dueDay ?? new ExpenseDueDay(15);
        $monthsOfYearV  = $monthsOfYear ?? [1, 4, 7, 10];

        $startVO = null;
        if ($startDate !== null) {
            $startVO = $startDate instanceof ExpenseStartDate
                ? $startDate
                : ExpenseStartDate::from($startDate->format('Y-m-d H:i:s'));
        }

        $endVO = null;
        if ($endDate !== null) {
            $endVO = $endDate instanceof ExpenseEndDate
                ? $endDate
                : ExpenseEndDate::from($endDate->format('Y-m-d H:i:s'));
        }

        return RecurringExpense::create(
            $idVal,
            $amountVal,
            $typeVal,
            $due,
            $monthsOfYearV,
            $startVO,
            $endVO,
            $description
        );
    }
}
