<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Domain;

use App\Tests\Context\Expense\Domain\ExpenseMother;
use PHPUnit\Framework\TestCase;

class AccountExpenseTest extends TestCase
{
    public function testItShouldAddAnExpenseToTheAccount(): void
    {
        $account = AccountMother::create();
        $expense = ExpenseMother::create(account: $account);

        $account->addExpense($expense);

        $this->assertCount(1, $account->expenses());
        $this->assertTrue($account->expenses()->contains($expense));
        $this->assertSame($account, $expense->account());
    }

    public function testItShouldNotAddAnExpenseIfItAlreadyExists(): void
    {
        $account = AccountMother::create();
        $expense = ExpenseMother::create(account: $account);

        $account->addExpense($expense);
        $account->addExpense($expense); // Add it again

        $this->assertCount(1, $account->expenses());
    }

    public function testItShouldRemoveAnExpenseFromTheAccount(): void
    {
        $account = AccountMother::create();
        $expense = ExpenseMother::create(account: $account);

        $account->addExpense($expense);
        $this->assertCount(1, $account->expenses());

        $account->removeExpense($expense);

        $this->assertCount(0, $account->expenses());
    }
}
