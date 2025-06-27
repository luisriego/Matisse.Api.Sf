<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseAmount;
use App\Tests\Context\Account\Domain\AccountMother;
use PHPUnit\Framework\TestCase;

final class EnterExpenseTest extends TestCase
{
    /** @test */
    public function test_it_should_create_a_valid_expense(): void
    {
        $id = ExpenseIdMother::create();
        $amount = ExpenseAmountMother::create();
        $account = AccountMother::create();
        $dueDate = new \DateTime();

        $expense = new Expense(
            $id->value(),
            $amount->value(),
            $account,
            $dueDate
        );

        $this->assertSame($id->value(), $expense->id());
        $this->assertSame($amount->value(), $expense->amount());
        $this->assertSame($account, $expense->account());
        $this->assertEquals($dueDate, $expense->dueDate());
        $this->assertNull($expense->paidAt());
    }

    /** @test */
    public function test_it_should_not_allow_negative_amount(): void
    {
        $this->expectException('App\Shared\Domain\Exception\InvalidArgumentException');

        new ExpenseAmount(-100); // This should throw the exception
    }

    /** @test */
    public function test_it_should_mark_as_paid(): void
    {
        $expense = ExpenseMother::create();

        $this->assertNull($expense->paidAt());

        $expense->markAsPaid();

        $this->assertNotNull($expense->paidAt());
    }

    /** @test */
    public function test_it_should_update_amount(): void
    {
        $expense = ExpenseMother::create();
        $originalAmount = $expense->amount();
        $newAmount = $originalAmount + 100;

        $expense->updateAmount($newAmount);

        $this->assertSame($newAmount, $expense->amount());
    }

    /** @test */
    public function test_it_should_update_due_date(): void
    {
        $expense = ExpenseMother::create();
        $newDueDate = new \DateTime('+1 month');

        $expense->updateDueDate($newDueDate);

        $this->assertEquals($newDueDate, $expense->dueDate());
    }

    /** @test */
    public function test_it_should_update_description(): void
    {
        $expense = ExpenseMother::create();
        $newDescription = 'Updated description';

        $expense->updateDescription($newDescription);

        $this->assertSame($newDescription, $expense->description());
    }

    /** @test */
    public function test_it_should_not_update_amount_when_paid(): void
    {
        $expense = ExpenseMother::create();
        $originalAmount = $expense->amount();

        $expense->markAsPaid();
        $expense->updateAmount($originalAmount + 100);

        $this->assertSame($originalAmount, $expense->amount());
    }

    /** @test */
    public function test_it_should_not_update_due_date_when_paid(): void
    {
        $expense = ExpenseMother::create();
        $originalDueDate = $expense->dueDate();

        $expense->markAsPaid();
        $expense->updateDueDate(new \DateTime('+1 month'));

        $this->assertEquals($originalDueDate, $expense->dueDate());
    }
}