<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Service;

use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\Slip\Domain\ValueObject\SlipDueDate;
use DateMalformedStringException;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

use function array_map;
use function abs;
use function count;
use function sprintf;

/**
 * Arma el mismo desglose que la generación de boletos, sin persistir agregados.
 */
readonly class SlipGenerationBreakdownBuilder
{
    public function __construct(
        private MonthlyExpenseAggregatorService $monthlyExpenseAggregator,
        private RecurringExpenseSlipContributionService $recurringExpenseSlipContribution,
        private SlipComponentBreakdownService $slipComponentBreakdownService,
        private GasConsumptionBreakdownResolver $gasConsumptionBreakdownResolver,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param array<int, Expense>           $expenses
     * @param array<int, RecurringExpense>  $recurringExpenses
     * @param array<int, ResidentUnit>     $residentUnits
     *
     * @return array<string, mixed>
     *
     * @throws DateMalformedStringException
     */
    public function build(
        array $expenses,
        array $recurringExpenses,
        array $residentUnits,
        int $expenseYear,
        int $expenseMonth,
        int $extraFeePerUnitCents = 0,
        int $reserveFundPerUnitCents = 0,
    ): array {
        $expenseTotals = $this->monthlyExpenseAggregator->aggregateTotals($expenses);
        $recurringPart = $this->recurringExpenseSlipContribution->contributionForMonth(
            $recurringExpenses,
            $expenseYear,
            $expenseMonth,
        );

        $mergedEqual = $expenseTotals['equal'] + $recurringPart['equal'];
        $mergedFraction = $expenseTotals['fraction'] + $recurringPart['fraction'];
        $individualByUnit = $expenseTotals['individualByUnit'];
        $n = count($residentUnits);

        $baseEqualPool = $mergedEqual;
        $syndicEqualPool = 0;

        $previousMonth = (new DateTimeImmutable(sprintf('%d-%d-01', $expenseYear, $expenseMonth)))->modify('-1 month');
        $gasYear = (int) $previousMonth->format('Y');
        $gasMonth = (int) $previousMonth->format('m');

        $unitIds = array_map(static fn(ResidentUnit $u) => $u->id(), $residentUnits);
        $gasBreakdown = $this->gasConsumptionBreakdownResolver->breakdownForMonth($gasYear, $gasMonth, $unitIds);
        $gasByUnit = $gasBreakdown['byUnit'];

        $dueDateContext = (new DateTimeImmutable(sprintf('%d-%d-01', $expenseYear, $expenseMonth)))->modify('+1 month');
        $dueYear = (int) $dueDateContext->format('Y');
        $dueMonth = (int) $dueDateContext->format('m');
        $dueDateTime = SlipDueDate::selectDueDate($dueYear, $dueMonth);

        $gasByUnitCents = [];
        foreach ($gasByUnit as $unitId => $gasDetail) {
            $gasByUnitCents[$unitId] = (int) ($gasDetail['gasCents'] ?? 0);
        }

        $computed = $this->slipComponentBreakdownService->build(
            $residentUnits,
            $baseEqualPool,
            $syndicEqualPool,
            $mergedFraction,
            $individualByUnit,
            $gasByUnitCents,
            $extraFeePerUnitCents,
            $reserveFundPerUnitCents,
        );

        $unitsOut = [];
        foreach ($computed['units'] as $unit) {
            $uid = $unit['residentUnitId'];
            $gasDetail = $gasByUnit[$uid] ?? null;
            $unitsOut[] = [
                ...$unit,
                'gasDetail' => $gasDetail !== null ? [
                    'previousReading' => $gasDetail['previousReading'],
                    'previousMonth' => $gasDetail['previousMonth'],
                    'currentReading' => $gasDetail['currentReading'],
                    'currentMonth' => $gasDetail['currentMonth'],
                    'consumptionM3' => $gasDetail['consumptionM3'],
                ] : null,
            ];
        }

        $warnings = [];
        if (abs($computed['totals']['differenceCents']) > 1) {
            $warnings[] = [
                'code' => 'COMPONENT_MISMATCH',
                'differenceCents' => $computed['totals']['differenceCents'],
            ];
            $this->logger->warning('Slip explain mismatch', [
                'targetMonth' => sprintf('%04d-%02d', $expenseYear, $expenseMonth),
                'differenceCents' => $computed['totals']['differenceCents'],
                'components' => $computed['components'],
                'totals' => $computed['totals'],
            ]);
        }

        return [
            'targetMonth' => sprintf('%04d-%02d', $expenseYear, $expenseMonth),
            'wouldBeDueDate' => $dueDateTime->format('Y-m-d'),
            'gasFromReadingsCalendarMonth' => sprintf('%04d-%02d', $gasYear, $gasMonth),
            'gasPricePerM3Cents' => $gasBreakdown['pricePerM3Cents'],
            'payingResidentsCount' => $n,
            'extraFeePerUnitCents' => $extraFeePerUnitCents,
            'reserveFundPerUnitCents' => $reserveFundPerUnitCents,
            'components' => [
                ...$computed['components'],
                'mergedEqualCents' => $mergedEqual,
                'baseEqualPoolCents' => $baseEqualPool,
                // Regla de negocio vigente: no separar síndico como componente adicional;
                // si existe en gastos del mes, ya está contenido dentro de base/equal.
                'syndicEqualPoolCents' => $syndicEqualPool,
                'mergedFractionCents' => $mergedFraction,
            ],
            'classificationSummary' => $this->classificationSummary($expenses),
            'expenseLines' => $this->expenseLines($expenses),
            'recurringLines' => $this->recurringLines($recurringExpenses, $expenseYear, $expenseMonth),
            'units' => $unitsOut,
            'totals' => $computed['totals'],
            'warnings' => $warnings,
        ];
    }

    /**
     * @param array<int, Expense> $expenses
     *
     * @return list<array<string, mixed>>
     */
    private function expenseLines(array $expenses): array
    {
        $out = [];
        foreach ($expenses as $expense) {
            $type = $expense->type();
            $classification = $this->monthlyExpenseAggregator->classifyForSlip($expense);
            $out[] = [
                'id' => $expense->id(),
                'amountCents' => $expense->amount(),
                'dueDate' => $expense->dueDate()->format('Y-m-d'),
                'typeCode' => $type?->code(),
                'distributionMethod' => $type?->distributionMethod(),
                'residentUnitId' => $expense->residentUnitId(),
                'description' => $expense->description(),
                'includedInSlipBase' => $classification['included'],
                'slipBucket' => $classification['bucket'],
                'classificationReason' => $classification['reason'],
            ];
        }

        return $out;
    }

    /**
     * @param array<int, Expense> $expenses
     *
     * @return array<string, int>
     */
    private function classificationSummary(array $expenses): array
    {
        $equalIncluded = 0;
        $fractionIncluded = 0;
        $excluded = 0;

        foreach ($expenses as $expense) {
            $classification = $this->monthlyExpenseAggregator->classifyForSlip($expense);
            if (!$classification['included']) {
                $excluded += $expense->amount();
                continue;
            }
            if ($classification['bucket'] === 'FRACTION') {
                $fractionIncluded += $expense->amount();
                continue;
            }
            $equalIncluded += $expense->amount();
        }

        return [
            'equalIncludedCents' => $equalIncluded,
            'fractionIncludedCents' => $fractionIncluded,
            'excludedCents' => $excluded,
            'baseIncludedTotalCents' => $equalIncluded + $fractionIncluded,
        ];
    }

    /**
     * @param array<int, RecurringExpense> $recurringExpenses
     *
     * @return list<array<string, mixed>>
     */
    private function recurringLines(array $recurringExpenses, int $year, int $month): array
    {
        $out = [];
        foreach ($recurringExpenses as $re) {
            $applies = $this->recurringExpenseSlipContribution->contributionForMonth([$re], $year, $month);
            $type = $re->type();
            $out[] = [
                'id' => $re->id(),
                'amountCents' => $re->amount(),
                'typeCode' => $type->code(),
                'distributionMethod' => $type->distributionMethod(),
                'hasPredefinedAmount' => $re->hasPredefinedAmount(),
                'isActive' => $re->isActive(),
                'countsTowardSlipEqualCents' => $applies['equal'],
                'countsTowardSlipFractionCents' => $applies['fraction'],
                'skippedAsIndividualInSlips' => strtoupper((string) $type->distributionMethod()) === ExpenseType::INDIVIDUAL,
            ];
        }

        return $out;
    }
}
