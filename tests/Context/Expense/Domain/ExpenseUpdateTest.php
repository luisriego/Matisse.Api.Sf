<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseDescription;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDate;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionProperty;

final class ExpenseUpdateTest extends TestCase
{
    /**
     * @test
     */
    public function testItShouldUpdateAmountWhenNotPaid(): void
    {
        $expense = ExpenseMother::create();
        $newAmount = new ExpenseAmount(1000);

        $expense->updateAmount($newAmount->value());

        $this->assertSame($newAmount->value(), $expense->amount());
    }

    /**
     * @test
     */
    public function testItShouldUpdateDueDateWhenNotPaid(): void
    {
        $expense = ExpenseMother::create();
        $newDueDate = new ExpenseDueDate(new DateTime('2023-12-31'));

        $expense->updateDueDate($newDueDate->toDateTime());

        $this->assertEquals($newDueDate->toDateTime()->format('Y-m-d'), $expense->dueDate()->format('Y-m-d'));
    }

    /**
     * @test
     */
    public function testItShouldUpdateDescriptionWhenNotPaid(): void
    {
        $expense = ExpenseMother::create();
        $newDescription = new ExpenseDescription('Updated description');

        $expense->updateDescription($newDescription->value());

        $this->assertSame($newDescription->value(), $expense->description());
    }

    /** @test
     * @throws ReflectionException
     */
    public function testItShouldNotUpdateWhenExpenseIsPaid(): void
    {
        $expense = ExpenseMother::create();
        $originalAmount = $expense->amount();
        $originalDueDate = $expense->dueDate();

        // Set expense as paid
        $paidAt = new DateTimeImmutable();
        $reflection = new ReflectionProperty($expense, 'paidAt');
        $reflection->setValue($expense, $paidAt);

        // Try to update
        $expense->updateAmount(9999);
        $expense->updateDueDate(new DateTime('2025-01-01'));

        // Verify no changes were made
        $this->assertSame($originalAmount, $expense->amount());
        $this->assertSame($originalDueDate, $expense->dueDate());
    }
}
