<?php

declare(strict_types=1);

namespace App\Context\Forecast\Domain\Service;

use App\Context\BillingPolicy\Domain\BillingPolicyResolverPort;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Slip\Domain\Service\GasConsumptionBreakdownResolver;
use App\Context\Slip\Domain\Service\RecurringExpenseSlipContributionService;
use App\Context\Slip\Domain\Service\SlipComponentBreakdownService;
use App\Context\Slip\Domain\Service\SyndicFeeSlipPoolAdjustmentService;
use App\Context\Slip\Domain\ValueObject\SlipDueDate;
use App\Shared\Domain\ValueObject\DateRange;
use DateMalformedStringException;
use DateTimeImmutable;

use function array_map;
use function count;
use function mb_strtoupper;
use function sprintf;

/**
 * Builds PREVISÃO-style projection for a target month without persisting accounting entries.
 */
final readonly class ForecastBuilder
{
    private const string GAS_EXPENSE_TYPE_CODE = 'SP3GA';

    public function __construct(
        private RecurringExpenseRepository $recurringExpenseRepository,
        private ResidentUnitRepository $residentUnitRepository,
        private ExpenseRepository $expenseRepository,
        private BillingPolicyResolverPort $billingPolicyResolver,
        private RecurringExpenseSlipContributionService $recurringExpenseSlipContribution,
        private SyndicFeeSlipPoolAdjustmentService $syndicFeeSlipPoolAdjustment,
        private SlipComponentBreakdownService $slipComponentBreakdownService,
        private GasConsumptionBreakdownResolver $gasConsumptionBreakdownResolver,
        private ExpectedExpenseFrequencyInferrer $frequencyInferrer,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws DateMalformedStringException
     */
    public function build(string $targetMonth, ?string $reconciliationMonth): array
    {
        [$targetYear, $targetMonthNum] = $this->parseYearMonth($targetMonth);
        $reconciliationMonth ??= $this->previousMonthString($targetMonth);

        $residentUnits = $this->residentUnitRepository->findAllActive();
        if ($residentUnits === []) {
            return [
                'targetMonth' => $targetMonth,
                'error' => 'no_active_resident_units',
                'message' => 'No hay unidades residenciales activas; no se puede calcular la previsión.',
            ];
        }

        $range = DateRange::fromMonth($targetYear, $targetMonthNum);
        $recurringExpenses = $this->recurringExpenseRepository->findActiveForDateRange($range);
        $policy = $this->billingPolicyResolver->resolve($targetMonth);

        $recurringPart = $this->recurringExpenseSlipContribution->forecastContributionForMonth(
            $recurringExpenses,
            $targetYear,
            $targetMonthNum,
        );

        $poolAdjustment = $this->syndicFeeSlipPoolAdjustment->adjust(
            [],
            $recurringExpenses,
            $targetYear,
            $targetMonthNum,
            $residentUnits,
            $recurringPart['equal'],
            [],
            $policy->syndicShareTotalCents(),
        );

        $previousMonth = (new DateTimeImmutable(sprintf('%04d-%02d-01', $targetYear, $targetMonthNum)))->modify('-1 month');
        $gasYear = (int) $previousMonth->format('Y');
        $gasMonth = (int) $previousMonth->format('m');
        $gasPriceOverride = $policy->hasPolicy() ? $policy->gasPricePerM3Cents() : null;

        $unitIds = array_map(static fn (ResidentUnit $u) => $u->id(), $residentUnits);
        $gasBreakdown = $this->gasConsumptionBreakdownResolver->breakdownForMonth(
            $gasYear,
            $gasMonth,
            $unitIds,
            $gasPriceOverride,
        );

        $gasByUnitCents = [];
        foreach ($gasBreakdown['byUnit'] as $unitId => $gasDetail) {
            $gasByUnitCents[$unitId] = (int) ($gasDetail['gasCents'] ?? 0);
        }

        $computed = $this->slipComponentBreakdownService->build(
            $residentUnits,
            $poolAdjustment['baseEqualPoolCents'],
            $poolAdjustment['syndicEqualPoolCents'],
            $recurringPart['fraction'],
            $poolAdjustment['individualByUnit'],
            $gasByUnitCents,
            $policy->extraFeePerUnitCents(),
            $policy->reserveFundPerUnitCents(),
        );

        $dueDate = SlipDueDate::selectDueDate($targetYear, $targetMonthNum);

        $unitsOut = [];
        foreach ($computed['units'] as $unit) {
            $uid = (string) $unit['residentUnitId'];
            $gasDetail = $gasBreakdown['byUnit'][$uid] ?? null;
            $unitsOut[] = [
                'residentUnitId' => $uid,
                'unit' => $unit['unit'],
                'idealFraction' => $unit['idealFraction'],
                'despesasPrevistasCents' => $unit['baseCents'],
                'syndicShareCents' => $unit['syndicCents'],
                'extraFeePerUnitCents' => $unit['extraCents'],
                'reserveFundPerUnitCents' => $unit['reserveCents'],
                'gasCents' => $unit['gasCents'],
                'totalCents' => $unit['totalCents'],
                'gasDetail' => $gasDetail,
            ];
        }

        $gasReadings = [];
        foreach ($residentUnits as $residentUnit) {
            $uid = $residentUnit->id();
            $detail = $gasBreakdown['byUnit'][$uid] ?? null;
            if ($detail === null) {
                continue;
            }
            $gasReadings[] = [
                'residentUnitId' => $uid,
                'unit' => $residentUnit->unit(),
                'previousReading' => $detail['previousReading'],
                'currentReading' => $detail['currentReading'],
                'consumptionM3' => $detail['consumptionM3'],
                'amountCents' => $detail['gasCents'],
            ];
        }

        $components = $computed['components'];

        return [
            'targetMonth' => $targetMonth,
            'reconciliationMonth' => $reconciliationMonth,
            'documentKind' => 'previsao',
            'isProjectionOnly' => true,
            'dueDate' => $dueDate->format('Y-m-d'),
            'billingPolicy' => [
                'targetMonth' => $policy->targetMonth(),
                'sourceMonth' => $policy->sourceMonth(),
                'extraFeePerUnitCents' => $policy->extraFeePerUnitCents(),
                'reserveFundPerUnitCents' => $policy->reserveFundPerUnitCents(),
                'syndicShareTotalCents' => $policy->syndicShareTotalCents(),
                'gasPricePerM3Cents' => $policy->gasPricePerM3Cents(),
            ],
            'units' => $unitsOut,
            'gas' => [
                'consumptionCalendarMonth' => sprintf('%04d-%02d', $gasYear, $gasMonth),
                'pricePerM3Cents' => $gasBreakdown['pricePerM3Cents'],
                'priceSource' => $policy->hasPolicy() && $policy->gasPricePerM3Cents() !== null
                    ? 'billing_policy' : 'latest_defined',
                'readings' => $gasReadings,
                'totalCents' => $gasBreakdown['gasTotalCents'],
            ],
            'expectedExpenseLines' => $this->buildExpectedExpenseLines(
                $recurringExpenses,
                $targetYear,
                $targetMonthNum,
                $reconciliationMonth,
            ),
            'totals' => [
                'despesasPrevistasCents' => $components['baseTotalCents'],
                'syndicShareTotalCents' => $components['syndicTotalCents'],
                'extraFeeTotalCents' => $components['extraTotalCents'],
                'reserveFundTotalCents' => $components['reserveTotalCents'],
                'gasTotalCents' => $components['gasTotalCents'],
                'boletoGrandTotalCents' => $components['grandTotalCents'],
            ],
            'meta' => [
                'payingResidentsCount' => count($residentUnits),
                'pendingExternalEstimates' => [
                    ['provider' => 'cemig', 'status' => 'not_implemented'],
                    ['provider' => 'copasa', 'status' => 'not_implemented'],
                ],
            ],
        ];
    }

    /**
     * @param array<int, RecurringExpense> $recurringExpenses
     *
     * @return list<array<string, mixed>>
     */
    private function buildExpectedExpenseLines(
        array $recurringExpenses,
        int $year,
        int $month,
        string $reconciliationMonth,
    ): array {
        $lines = [];

        foreach ($recurringExpenses as $recurring) {
            if (!$recurring instanceof RecurringExpense || !$recurring->isActive()) {
                continue;
            }

            $type = $recurring->type();
            $code = mb_strtoupper((string) $type->code());
            $method = mb_strtoupper((string) $type->distributionMethod());

            if ($code === self::GAS_EXPENSE_TYPE_CODE || $method === ExpenseType::INDIVIDUAL) {
                continue;
            }

            $applies = $this->recurringExpenseSlipContribution->forecastContributionForMonth(
                [$recurring],
                $year,
                $month,
            );
            $appliesThisMonth = $applies['equal'] > 0 || $applies['fraction'] > 0;

            $frequency = $this->frequencyInferrer->infer($recurring->monthsOfYear());
            $hasPredefined = $recurring->hasPredefinedAmount();
            $lastReconciledMonth = $this->expenseRepository->findLatestDueDateMonthByRecurringExpenseId(
                $recurring->id(),
            );

            $line = [
                'recurringExpenseId' => $recurring->id(),
                'label' => $recurring->description() ?? $type->name(),
                'category' => 'fixed',
                'frequency' => $frequency['frequency'],
                'monthsOfYear' => $frequency['monthsOfYear'],
                'appliesThisMonth' => $appliesThisMonth,
                'amountCents' => $recurring->amount(),
                'amountSource' => $hasPredefined ? 'pattern_fixed' : 'last_reconciled',
                'amountKind' => $hasPredefined ? 'fixed' : 'variable',
            ];

            if (!$hasPredefined) {
                $line['lastReconciledMonth'] = $lastReconciledMonth ?? $reconciliationMonth;
            }

            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function parseYearMonth(string $ym): array
    {
        if (1 !== preg_match('/^(\d{4})-(\d{2})$/', $ym, $matches)) {
            throw new \InvalidArgumentException('Invalid targetMonth. Expected YYYY-MM.');
        }

        return [(int) $matches[1], (int) $matches[2]];
    }

    private function previousMonthString(string $ym): string
    {
        [$year, $month] = $this->parseYearMonth($ym);
        if ($month === 1) {
            return sprintf('%04d-12', $year - 1);
        }

        return sprintf('%04d-%02d', $year, $month - 1);
    }
}
