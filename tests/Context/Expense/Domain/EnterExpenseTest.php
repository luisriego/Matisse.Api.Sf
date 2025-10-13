<?php declare(strict_types=1);

namespace App\Tests\Context\Expense\Domain;

use App\Context\Account\Domain\Account;
use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use DateTime;
use PHPUnit\Framework\TestCase;

final class EnterExpenseTest extends TestCase
{
    /** @test */
    public function test_it_creates_a_valid_expense(): void
    {
        $id = '00000000-0000-0000-0000-000000000001';
        $amountValue = 5000;
        $type = $this->createMock(ExpenseType::class);
        $account = $this->createMock(Account::class);
        $dueDate = new DateTime('2025-01-01');

        $expense = new Expense(
            $id,
            $amountValue,
            $type,
            $account,
            $dueDate
        );

        $this->assertSame($id, $expense->id());
        $this->assertSame($amountValue, $expense->amount());
        $this->assertSame($account, $expense->account());
        $this->assertEquals($dueDate, $expense->dueDate());
        $this->assertNull($expense->paidAt());
    }

    /** @test */
    public function test_it_throws_when_negative_amount(): void
    {
        $this->expectException(\App\Shared\Domain\Exception\InvalidArgumentException::class);

        new ExpenseAmount(-100);
    }
}