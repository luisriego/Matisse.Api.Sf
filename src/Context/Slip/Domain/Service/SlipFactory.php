<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Service;

use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\ValueObject\SlipAmount;
use App\Context\Slip\Domain\ValueObject\SlipDueDate;
use App\Context\Slip\Domain\ValueObject\SlipId;
use App\Shared\Domain\ValueObject\Uuid;
use DateMalformedStringException;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

use function array_map;
use function sprintf;

readonly class SlipFactory
{
    public function __construct(
        private MonthlyExpenseAggregatorService $monthlyExpenseAggregator,
        private RecurringExpenseSlipContributionService $recurringExpenseSlipContribution,
        private SyndicFeeSlipPoolAdjustmentService $syndicFeeSlipPoolAdjustment,
        private SlipComponentBreakdownService $slipComponentBreakdownService,
        private GasExpenseByUnitResolver $gasExpenseByUnitResolver,
        private LoggerInterface $logger,
        private ?GasConsumptionBreakdownResolver $gasConsumptionBreakdownResolver = null,
    ) {}

    /**
     * @param array<int, Expense>          $expenses
     * @param array<int, RecurringExpense> $recurringExpenses
     * @param ResidentUnit[]               $residentUnits
     *
     * @return Slip[]
     *
     * @throws DateMalformedStringException
     */
    public function createFromExpensesAndUnits(
        array $expenses,
        array $recurringExpenses,
        array $residentUnits,
        int $expenseYear,
        int $expenseMonth,
        int $extraFeePerUnitCents = 0,
        int $reserveFundPerUnitCents = 0,
        ?int $syndicShareTotalCents = null,
        ?int $gasPricePerM3CentsOverride = null,
    ): array {
        if (empty($residentUnits)) {
            return [];
        }

        $expenseTotals = $this->monthlyExpenseAggregator->aggregateTotals($expenses);
        $recurringPart = $this->recurringExpenseSlipContribution->contributionForMonth(
            $recurringExpenses,
            $expenseYear,
            $expenseMonth,
            $expenses,
        );

        $mergedEqual = $expenseTotals['equal'] + $recurringPart['equal'];
        $totalFractionBasedExpenses = $expenseTotals['fraction'] + $recurringPart['fraction'];
        $individualByUnit = $expenseTotals['individualByUnit'];

        $poolAdjustment = $this->syndicFeeSlipPoolAdjustment->adjust(
            $expenses,
            $recurringExpenses,
            $expenseYear,
            $expenseMonth,
            $residentUnits,
            $mergedEqual,
            $individualByUnit,
            $syndicShareTotalCents ?? SyndicFeeSlipPoolAdjustmentService::SYNDIC_SHARE_TOTAL_CENTS,
        );
        $totalEquallyDividedExpenses = $poolAdjustment['baseEqualPoolCents'];
        $syndicEqualPool = $poolAdjustment['syndicEqualPoolCents'];
        $individualByUnit = $poolAdjustment['individualByUnit'];
        $this->logger->info(sprintf(
            'Aggregated totals for %d-%d: Equal: %.2f, Fraction: %.2f, Individual: %.2f, RecurringEqual: %.2f, RecurringFraction: %.2f',
            $expenseYear,
            $expenseMonth,
            $expenseTotals['equal'] / 100,
            $expenseTotals['fraction'] / 100,
            $expenseTotals['individual'] / 100,
            $recurringPart['equal'] / 100,
            $recurringPart['fraction'] / 100,
        ));

        $dueDateContext = (new DateTimeImmutable(sprintf('%d-%d-01', $expenseYear, $expenseMonth)))->modify('+1 month');
        $dueYear = (int) $dueDateContext->format('Y');
        $dueMonth = (int) $dueDateContext->format('m');
        $dueDateTime = SlipDueDate::selectDueDate($dueYear, $dueMonth);
        $dueDate = new SlipDueDate($dueDateTime);

        $slips = [];

        $previousMonth = (new DateTimeImmutable(sprintf('%d-%d-01', $expenseYear, $expenseMonth)))->modify('-1 month');
        $gasYear = (int) $previousMonth->format('Y');
        $gasMonth = (int) $previousMonth->format('m');

        if ($this->gasConsumptionBreakdownResolver !== null) {
            $gasBreakdown = $this->gasConsumptionBreakdownResolver->breakdownForMonth(
                $gasYear,
                $gasMonth,
                array_map(static fn (ResidentUnit $u) => $u->id(), $residentUnits),
                $gasPricePerM3CentsOverride,
            );

            if ($gasBreakdown['gasTotalCents'] > 0) {
                $gasExpensesByUnit = [];

                foreach ($gasBreakdown['byUnit'] as $unitId => $unitData) {
                    $gasExpensesByUnit[$unitId] = $unitData['gasCents'];
                }
            } else {
                $gasExpensesByUnit = $this->gasExpenseByUnitResolver->sumByResidentUnitForCalendarMonth(
                    $gasYear,
                    $gasMonth,
                );
            }
        } else {
            $gasExpensesByUnit = $this->gasExpenseByUnitResolver->sumByResidentUnitForCalendarMonth(
                $gasYear,
                $gasMonth,
            );
        }

        $breakdown = $this->slipComponentBreakdownService->build(
            $residentUnits,
            $totalEquallyDividedExpenses,
            $syndicEqualPool,
            $totalFractionBasedExpenses,
            $individualByUnit,
            $gasExpensesByUnit,
            $extraFeePerUnitCents,
            $reserveFundPerUnitCents,
        );
        $residentById = [];

        foreach ($residentUnits as $residentUnit) {
            $residentById[$residentUnit->id()] = $residentUnit;
        }

        foreach ($breakdown['units'] as $unit) {
            $amountInCents = (int) $unit['totalCents'];

            if ($amountInCents <= 0) {
                $this->logger->info(sprintf(
                    'Calculated amount for unit %s is zero or negative. Skipping slip creation.',
                    (string) $unit['unit'],
                ));

                continue;
            }

            $residentUnit = $residentById[$unit['residentUnitId']] ?? null;

            if ($residentUnit === null) {
                continue;
            }

            $id = new SlipId(Uuid::random()->value());
            $slipAmount = new SlipAmount($amountInCents);
            $slips[] = Slip::createForUnit($id, $slipAmount, $residentUnit, $dueDate);
        }

        return $slips;
    }
}
