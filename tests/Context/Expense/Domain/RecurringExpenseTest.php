<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDay;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class RecurringExpenseTest extends TestCase
{
    /** @test
     * @throws \DateMalformedStringException
     */
    public function test_it_creates_recurring_expense_with_correct_properties(): void
    {
        // Arrange
        $id          = ExpenseIdMother::create();
        $amount      = ExpenseAmountMother::create();
        $type        = ExpenseTypeMother::create();
        $dueDay      = new ExpenseDueDay(15);
        $months      = [1, 6, 12];
        $startDate   = null;
        $endDate     = null;
        $description = 'Pago recurrente';
        $notes       = 'Sin comentarios';

        // Act
        $recurring = RecurringExpense::create(
            $id,
            $amount,
            $type,
            $dueDay,
            $months,
            $startDate,
            $endDate,
            $description,
            $notes
        );

        // Assert
        $this->assertSame($id->value(), $recurring->id());
        $this->assertSame($amount->value(), $recurring->amount());
        $this->assertSame($type, $recurring->type());
        $this->assertSame(15, $recurring->dueDay());
        $this->assertSame($months, $recurring->monthsOfYear());
        $this->assertEquals(
            new DateTime("2025-01-01 12:00:00"),
            $recurring->startDate()
        );
        $this->assertEquals(
            new DateTime("2025-12-31 12:00:00"),
            $recurring->endDate()
        );
        $this->assertSame($description, $recurring->description());
        $this->assertSame($notes, $recurring->notes());
        $this->assertInstanceOf(DateTimeImmutable::class, $recurring->createdAt());
        $this->assertTrue($recurring->isActive());
        $this->assertCount(0, $recurring->expenses());
    }

    /** @test
     * @throws \DateMalformedStringException
     */
    public function test_it_adds_and_removes_child_expenses(): void
    {
        // Arrange
        $recurring = RecurringExpense::create(
            ExpenseIdMother::create(),
            ExpenseAmountMother::create(),
            ExpenseTypeMother::create(),
            new ExpenseDueDay(15),
            [1, 2],
            null,
            null
        );
        $expense = ExpenseMother::create(); // returns an Expense with no recurring link

        // Act & Assert: add
        $recurring->addExpense($expense);
        $this->assertCount(1, $recurring->expenses());
        $this->assertSame($recurring, $expense->recurringExpense());

        // Act & Assert: remove
        $recurring->removeExpense($expense);
        $this->assertCount(0, $recurring->expenses());
        $this->assertNull($expense->recurringExpense());
    }
}