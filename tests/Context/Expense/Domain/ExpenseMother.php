<?php

namespace App\Tests\Context\Expense\Domain;

use App\Context\Account\Domain\Account;
use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseId;
use App\Context\Expense\Domain\ExpenseAmount;
use App\Context\Expense\Domain\ExpenseDueDate;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\ExpenseTypeId;
use App\Tests\Context\Account\Domain\AccountMother;

final class ExpenseMother
{
    public static function create(
        ?ExpenseId $id = null,
        ?ExpenseAmount $amount = null,
        ?Account $account = null,
        ?ExpenseDueDate $dueDate = null,
        ?ExpenseType $type = null
    ): Expense {
        $id      = $id      ?? ExpenseIdMother::create();
        $amount  = $amount  ?? ExpenseAmountMother::create();
        $account = $account ?? AccountMother::create();
        $dueDate = $dueDate?->toDateTime() ?? new \DateTime();
        $type    = $type    ?? new ExpenseType(
            ExpenseTypeId::random()->value(),
            'DEFAULT_CODE',
            'Default Type'
        );

        return new Expense(
            $id->value(),
            $amount->value(),
            $type,
            $account,
            $dueDate
        );
    }
}