<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Domain;

use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use PHPUnit\Framework\TestCase;

class AccountExpenseTest extends TestCase
{
    public function test_it_should_add_an_expense_to_the_account(): void
    {
        $account = AccountMother::create();
        $expense = ExpenseMother::create(account: $account);
        $expenseId = ExpenseId::fromString($expense->id());

        $account->addExpense($expenseId);

        $this->assertCount(1, $account->expenses());
        $this->assertTrue($account->expenses()->contains($expenseId));
    }

    public function test_it_should_not_add_an_expense_if_it_already_exists(): void
    {
        $account = AccountMother::create();
        $expense = ExpenseMother::create(account: $account);
        $expenseId = ExpenseId::fromString($expense->id());

        $account->addExpense($expenseId);
        $account->addExpense($expenseId); // Add it again

        $this->assertCount(1, $account->expenses());
    }

    public function test_it_should_remove_an_expense_from_the_account(): void
    {
        $account = AccountMother::create();
        $expense = ExpenseMother::create(account: $account);
        $expenseId = ExpenseId::fromString($expense->id());

        $account->addExpense($expenseId);
        $this->assertCount(1, $account->expenses());

        $account->removeExpense($expenseId);

        $this->assertCount(0, $account->expenses());
    }
}
