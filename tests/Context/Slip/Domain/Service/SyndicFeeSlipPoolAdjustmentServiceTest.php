<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Domain\Service;

use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\Slip\Domain\Service\MonthlyExpenseAggregatorService;
use App\Context\Slip\Domain\Service\RecurringExpenseSlipContributionService;
use App\Context\Slip\Domain\Service\SlipComponentBreakdownService;
use App\Context\Slip\Domain\Service\SyndicFeeSlipPoolAdjustmentService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class SyndicFeeSlipPoolAdjustmentServiceTest extends TestCase
{
    private SyndicFeeSlipPoolAdjustmentService $adjustment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adjustment = new SyndicFeeSlipPoolAdjustmentService(
            new RecurringExpenseSlipContributionService(),
            new MonthlyExpenseAggregatorService(new NullLogger()),
        );
    }

    public function testSplitsPf1se670Into600SyndicEqualAnd70OnUnit401(): void
    {
        $units = $this->fiveUnits();
        $recurring = [$this->pf1seRecurring(67000)];

        $result = $this->adjustment->adjust(
            [],
            $recurring,
            2026,
            3,
            $units,
            67000,
            [],
        );

        $this->assertSame(0, $result['baseEqualPoolCents']);
        $this->assertSame(60000, $result['syndicEqualPoolCents']);
        $this->assertSame(67000, $result['pf1seTotalCents']);
        $this->assertSame(60000, $result['syndicShareCents']);
        $this->assertSame(7000, $result['internetShareCents']);
        $this->assertSame('u-401', $result['internetChargedToUnitId']);
        $this->assertSame(7000, $result['individualByUnit']['u-401']);
    }

    public function testBreakdownShowsSyndic600AndInternetOn401ForFiveUnits(): void
    {
        $units = $this->fiveUnits();
        $recurring = [$this->pf1seRecurring(67000)];
        $breakdownService = new SlipComponentBreakdownService();

        $pools = $this->adjustment->adjust([], $recurring, 2026, 3, $units, 67000, []);

        $result = $breakdownService->build(
            $units,
            $pools['baseEqualPoolCents'],
            $pools['syndicEqualPoolCents'],
            0,
            $pools['individualByUnit'],
            [],
            0,
            0,
        );

        $this->assertSame(60000, $result['components']['syndicTotalCents']);
        $this->assertSame(7000, $result['components']['grandTotalCents'] - 60000);

        $byUnit = [];

        foreach ($result['units'] as $row) {
            $byUnit[$row['unit']] = $row;
        }

        $this->assertSame(12000, $byUnit['101']['syndicCents']);
        $this->assertSame(12000, $byUnit['201']['syndicCents']);
        $this->assertSame(12000, $byUnit['301']['syndicCents']);
        $this->assertSame(12000, $byUnit['401']['syndicCents']);
        $this->assertSame(12000, $byUnit['501']['syndicCents']);
        $this->assertSame(7000, $byUnit['401']['individualNonGasCents']);
        $this->assertSame(19000, $byUnit['401']['totalCents']);
        $this->assertSame(12000, $byUnit['101']['totalCents']);
    }

    public function testNoAdjustmentWhenPf1seAbsent(): void
    {
        $units = $this->fiveUnits();

        $result = $this->adjustment->adjust([], [], 2026, 3, $units, 500000, ['u-101' => 1000]);

        $this->assertSame(500000, $result['baseEqualPoolCents']);
        $this->assertSame(0, $result['syndicEqualPoolCents']);
        $this->assertSame(['u-101' => 1000], $result['individualByUnit']);
    }

    public function testPf1seReconciledExpenseSupersedesRecurringTemplate(): void
    {
        $units = $this->fiveUnits();
        $recurring = [$this->pf1seRecurring(67000)];
        $expense = $this->pf1seExpense(67000);

        $mergedEqual = 67000;
        $result = $this->adjustment->adjust(
            [$expense],
            $recurring,
            2026,
            3,
            $units,
            $mergedEqual,
            [],
        );

        $this->assertSame(0, $result['baseEqualPoolCents']);
        $this->assertSame(60000, $result['syndicEqualPoolCents']);
        $this->assertSame(67000, $result['pf1seTotalCents']);
        $this->assertSame(7000, $result['internetShareCents']);
    }

    /**
     * @return array<int, ResidentUnit>
     */
    private function fiveUnits(): array
    {
        return [
            $this->unit('u-101', '101'),
            $this->unit('u-201', '201'),
            $this->unit('u-301', '301'),
            $this->unit('u-401', '401'),
            $this->unit('u-501', '501'),
        ];
    }

    private function unit(string $id, string $unit): ResidentUnit
    {
        $mock = $this->createMock(ResidentUnit::class);
        $mock->method('id')->willReturn($id);
        $mock->method('unit')->willReturn($unit);
        $mock->method('idealFraction')->willReturn(0.2);

        return $mock;
    }

    private function pf1seRecurring(int $amountCents): RecurringExpense
    {
        $type = $this->createMock(ExpenseType::class);
        $type->method('code')->willReturn('PF1SE');
        $type->method('id')->willReturn('type-pf1se');
        $type->method('distributionMethod')->willReturn('EQUAL');

        $recurring = $this->createMock(RecurringExpense::class);
        $recurring->method('type')->willReturn($type);
        $recurring->method('amount')->willReturn($amountCents);
        $recurring->method('isActive')->willReturn(true);
        $recurring->method('hasPredefinedAmount')->willReturn(true);
        $recurring->method('startDate')->willReturn(new DateTimeImmutable('2020-01-01'));
        $recurring->method('endDate')->willReturn(null);
        $recurring->method('monthsOfYear')->willReturn(null);
        $recurring->method('id')->willReturn('recurring-pf1se');

        return $recurring;
    }

    private function pf1seExpense(int $amountCents): Expense
    {
        $type = $this->createMock(ExpenseType::class);
        $type->method('code')->willReturn('PF1SE');
        $type->method('id')->willReturn('type-pf1se');
        $type->method('distributionMethod')->willReturn('EQUAL');

        $expense = $this->createMock(Expense::class);
        $expense->method('type')->willReturn($type);
        $expense->method('amount')->willReturn($amountCents);
        $expense->method('isActive')->willReturn(true);
        $expense->method('recurringExpense')->willReturn(null);

        return $expense;
    }
}
