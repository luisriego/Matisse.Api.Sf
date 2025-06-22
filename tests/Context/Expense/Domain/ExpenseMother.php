<?php

namespace App\Tests\Context\Expense\Domain;

use App\Context\Account\Domain\Account;
use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseId;
use App\Context\Expense\Domain\ExpenseAmount;
use App\Context\Expense\Domain\ExpenseDueDate;
use App\Tests\Context\Account\Domain\AccountMother;

final class ExpenseMother
{
    public static function create(
        ?ExpenseId $id = null,
        ?ExpenseAmount $amount = null,
        ?Account $account = null,
        ?ExpenseDueDate $dueDate = null
    ): Expense {
        $dueDate = new \DateTime();
        return new Expense(
            $id ?? ExpenseIdMother::create(),
            $amount?->value() ?? ExpenseAmountMother::create()->value(), // Ensure int type
            $account ?? AccountMother::create(),
            $dueDate
        );
    }
}