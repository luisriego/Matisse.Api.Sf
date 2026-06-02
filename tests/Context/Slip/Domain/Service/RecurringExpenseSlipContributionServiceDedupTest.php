<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Domain\Service;

use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDay;
use App\Context\Expense\Domain\ValueObject\ExpenseEndDate;
use App\Context\Expense\Domain\ValueObject\ExpenseStartDate;
use App\Context\Slip\Domain\Service\RecurringExpenseSlipContributionService;
use App\Tests\Context\Expense\Domain\ExpenseIdMother;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Context\Expense\Domain\RecurringExpenseMother;
use App\Tests\Shared\Domain\UuidMother;
use DateTime;
use PHPUnit\Framework\TestCase;

use function range;

final class RecurringExpenseSlipContributionServiceDedupTest extends TestCase
{
    private RecurringExpenseSlipContributionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RecurringExpenseSlipContributionService();
    }

    public function testFixedRecurringContributesWhenNoReconciledExpense(): void
    {
        $type = $this->equalType('UTIL1');
        $recurring = $this->fixedRecurring($type, 25_000);

        $slice = $this->service->contributionForMonth([$recurring], 2026, 1);

        self::assertSame(25_000, $slice['equal']);
    }

    public function testSkipsRecurringWhenExpenseIsLinkedToSameRecurring(): void
    {
        $type = $this->equalType('UTIL1');
        $recurring = $this->fixedRecurring($type, 25_000);
        $expense = ExpenseMother::create(amount: 26_500, type: $type, dueDate: new DateTime('2026-01-10'));
        $expense->setRecurringExpense($recurring);

        $slice = $this->service->contributionForMonth([$recurring], 2026, 1, [$expense]);

        self::assertSame(0, $slice['equal']);
        self::assertSame(0, $slice['fraction']);
    }

    public function testSkipsRecurringWhenReconciledExpenseHasSameTypeWithoutLink(): void
    {
        $type = $this->equalType('PF1SE');
        $recurring = $this->fixedRecurring($type, 67_000);
        $expense = ExpenseMother::create(amount: 67_000, type: $type, dueDate: new DateTime('2026-01-05'));

        $slice = $this->service->contributionForMonth([$recurring], 2026, 1, [$expense]);

        self::assertSame(0, $slice['equal']);
    }

    public function testForecastStillCountsRecurringWhenReconciledExpenseExists(): void
    {
        $type = $this->equalType('UTIL1');
        $recurring = $this->fixedRecurring($type, 18_074);
        $expense = ExpenseMother::create(amount: 19_200, type: $type, dueDate: new DateTime('2026-01-10'));
        $expense->setRecurringExpense($recurring);

        $slipSlice = $this->service->contributionForMonth([$recurring], 2026, 1, [$expense]);
        $forecastSlice = $this->service->forecastContributionForMonth([$recurring], 2026, 1);

        self::assertSame(0, $slipSlice['equal']);
        self::assertSame(18_074, $forecastSlice['equal']);
    }

    public function testDifferentTypeExpenseDoesNotSuppressRecurring(): void
    {
        $recurringType = $this->equalType('UTIL1');
        $otherType = $this->equalType('UTIL2');
        $recurring = $this->fixedRecurring($recurringType, 10_000);
        $expense = ExpenseMother::create(amount: 10_000, type: $otherType, dueDate: new DateTime('2026-01-10'));

        $slice = $this->service->contributionForMonth([$recurring], 2026, 1, [$expense]);

        self::assertSame(10_000, $slice['equal']);
    }

    private function equalType(string $code): ExpenseType
    {
        return new ExpenseType(
            UuidMother::create(),
            $code,
            $code,
            ExpenseType::EQUAL,
        );
    }

    private function fixedRecurring(ExpenseType $type, int $amountCents): RecurringExpense
    {
        return RecurringExpenseMother::create(
            id: ExpenseIdMother::create(),
            expenseType: $type,
            dueDay: new ExpenseDueDay(10),
            monthsOfYear: range(1, 12),
            startDate: ExpenseStartDate::from('2020-01-01'),
            endDate: ExpenseEndDate::from('2099-12-31'),
            description: 'Fixed recurring',
            amount: new ExpenseAmount($amountCents),
        );
    }
}
