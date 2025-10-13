<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Domain;

use App\Tests\Context\Expense\Domain\ExpenseMother;
use PHPUnit\Framework\TestCase;

class AccountExpenseTest extends TestCase
{
    public function test_it_should_add_an_expense_to_the_account(): void
    {
        $account = AccountMother::create();
        $expense = ExpenseMother::create(account: $account);

        $account->addExpense($expense);

        $this->assertCount(1, $account->expenses());
        $this->assertTrue($account->expenses()->contains($expense));
        $this->assertSame($account, $expense->account());
    }

    public function test_it_should_not_add_an_expense_if_it_already_exists(): void
    {
        $account = AccountMother::create();
        $expense = ExpenseMother::create(account: $account);

        $account->addExpense($expense);
        $account->addExpense($expense); // Add it again

        $this->assertCount(1, $account->expenses());
    }

    public function test_it_should_remove_an_expense_from_the_account(): void
    {
        $account = AccountMother::create();
        $expense = ExpenseMother::create(account: $account);

        $account->addExpense($expense);
        $this->assertCount(1, $account->expenses());

        $account->removeExpense($expense);

        $this->assertCount(0, $account->expenses());
    }
}
