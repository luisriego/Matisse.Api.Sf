<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\ExpenseAmount;
use App\Context\Expense\Domain\ExpenseDescription;
use App\Context\Expense\Domain\ExpenseDueDate;
use PHPUnit\Framework\TestCase;

final class ExpenseUpdateTest extends TestCase
{
    /** @test */
    public function test_it_should_update_amount_when_not_paid(): void
    {
        $expense = ExpenseMother::create();
        $newAmount = new ExpenseAmount(1000);

        $expense->updateAmount($newAmount->value());

        $this->assertSame($newAmount->value(), $expense->amount());
    }

    /** @test */
    public function test_it_should_update_due_date_when_not_paid(): void
    {
        $expense = ExpenseMother::create();
        $newDueDate = new ExpenseDueDate(new \DateTime('2023-12-31'));

        $expense->updateDueDate($newDueDate->toDateTime());

        $this->assertEquals($newDueDate->toDateTime()->format('Y-m-d'), $expense->dueDate()->format('Y-m-d'));
    }

    /** @test */
    public function test_it_should_update_description_when_not_paid(): void
    {
        $expense = ExpenseMother::create();
        $newDescription = new ExpenseDescription('Updated description');

        $expense->updateDescription($newDescription->value());

        $this->assertSame($newDescription->value(), $expense->description());
    }

    /** @test
     * @throws \ReflectionException
     */
    public function test_it_should_not_update_when_expense_is_paid(): void
    {
        $expense = ExpenseMother::create();
        $originalAmount = $expense->amount();
        $originalDueDate = $expense->dueDate();

        // Set expense as paid
        $paidAt = new \DateTimeImmutable();
        $reflection = new \ReflectionProperty($expense, 'paidAt');
        $reflection->setValue($expense, $paidAt);

        // Try to update
        $expense->updateAmount(9999);
        $expense->updateDueDate(new \DateTime('2025-01-01'));

        // Verify no changes were made
        $this->assertSame($originalAmount, $expense->amount());
        $this->assertSame($originalDueDate, $expense->dueDate());
    }
}