<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\Bus\ExpenseWasCompensated;
use PHPUnit\Framework\TestCase;

class CompensateExpenseTest extends TestCase
{
    public function test_it_should_compensate_an_expense_and_set_amount_to_zero(): void
    {
        $expense = ExpenseMother::create();
        $initialAmount = $expense->amount();

        $expense->compensate();

        $this->assertSame(0, $expense->amount());

        $events = $expense->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ExpenseWasCompensated::class, $events[0]);
        $this->assertSame($expense->id(), $events[0]->aggregateId());
        $this->assertSame(-$initialAmount, $events[0]->toPrimitives()['amount']);
    }

    public function test_it_should_not_compensate_if_no_account_is_associated(): void
    {
        $expense = ExpenseMother::createWithNoAccount();
        $initialAmount = $expense->amount();

        $expense->compensate();

        $this->assertSame($initialAmount, $expense->amount());
        $events = $expense->pullDomainEvents();
        $this->assertCount(0, $events);
    }
}
