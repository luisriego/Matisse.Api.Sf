<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Domain\Service;

use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\ValueObject\ExpenseAmount;
use App\Context\Expense\Domain\ValueObject\ExpenseDueDay;
use App\Context\Expense\Domain\ValueObject\ExpenseEndDate;
use App\Context\Expense\Domain\ValueObject\ExpenseId;
use App\Context\Expense\Domain\ValueObject\ExpenseStartDate;
use App\Context\Slip\Domain\Service\RecurringExpenseSlipContributionService;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Shared\Domain\UuidMother;
use PHPUnit\Framework\TestCase;

final class RecurringExpenseSlipContributionServiceForecastTest extends TestCase
{
    public function test_forecast_contribution_includes_variable_recurring_amounts(): void
    {
        $recurring = $this->variableRecurring(18074);

        $service = new RecurringExpenseSlipContributionService();
        $slice = $service->forecastContributionForMonth([$recurring], 2022, 9);

        $this->assertSame(18074, $slice['equal']);
        $this->assertSame(0, $slice['fraction']);
    }

    public function test_slip_contribution_still_skips_variable_recurring_amounts(): void
    {
        $recurring = $this->variableRecurring(18074);

        $service = new RecurringExpenseSlipContributionService();
        $slice = $service->contributionForMonth([$recurring], 2022, 9);

        $this->assertSame(0, $slice['equal']);
        $this->assertSame(0, $slice['fraction']);
    }

    private function variableRecurring(int $amountCents): RecurringExpense
    {
        $type = ExpenseTypeMother::create();

        return RecurringExpense::create(
            new ExpenseId(UuidMother::create()),
            UuidMother::create(),
            new ExpenseAmount($amountCents),
            $type,
            new ExpenseDueDay(10),
            range(1, 12),
            ExpenseStartDate::from('2020-01-01'),
            ExpenseEndDate::from('2099-12-31'),
            'Cemig',
            null,
            false,
        );
    }
}
