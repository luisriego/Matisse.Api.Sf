<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Service;

use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\RecurringExpense;
use App\Context\ResidentUnit\Domain\ResidentUnit;

use function mb_strtoupper;
use function sprintf;
use function trim;

/**
 * Desglosa la línea PF1SE (taxa mensal do síndico) en boleto:
 * - R$ 600,00 repartidos en partes iguales (componente síndico),
 * - el resto (p. ej. R$ 70,00 internet/CFTV) imputado al apartamento 401.
 *
 * Convención del condominio: 670 = 600 + 70; no se mezcla todo en el pool igualitario.
 */
final readonly class SyndicFeeSlipPoolAdjustmentService
{
    public const string SYNDIC_EXPENSE_TYPE_CODE = 'PF1SE';

    /** Total del rateo del síndico (R$ 600,00). */
    public const int SYNDIC_SHARE_TOTAL_CENTS = 60_000;

    /** Apartamento que asume internet/CFTV de cámaras dentro de la línea PF1SE. */
    public const string INTERNET_CHARGED_TO_UNIT = '401';

    public function __construct(
        private RecurringExpenseSlipContributionService $recurringExpenseSlipContribution,
        private MonthlyExpenseAggregatorService $monthlyExpenseAggregator,
    ) {}

    /**
     * @param array<int, Expense>          $expenses
     * @param array<int, RecurringExpense> $recurringExpenses
     * @param array<int, ResidentUnit>     $residentUnits
     * @param array<string, int>           $individualByUnit
     *
     * @return array{
     *     baseEqualPoolCents: int,
     *     syndicEqualPoolCents: int,
     *     individualByUnit: array<string, int>,
     *     pf1seTotalCents: int,
     *     syndicShareCents: int,
     *     internetShareCents: int,
     *     internetChargedToUnitId: ?string,
     * }
     */
    public function adjust(
        array $expenses,
        array $recurringExpenses,
        int $expenseYear,
        int $expenseMonth,
        array $residentUnits,
        int $mergedEqualCents,
        array $individualByUnit,
        int $syndicShareTotalCents = self::SYNDIC_SHARE_TOTAL_CENTS,
    ): array {
        $pf1seTotal = $this->pf1seTotalCentsForMonth(
            $expenses,
            $recurringExpenses,
            $expenseYear,
            $expenseMonth,
        );

        if ($pf1seTotal <= 0) {
            return [
                'baseEqualPoolCents' => $mergedEqualCents,
                'syndicEqualPoolCents' => 0,
                'individualByUnit' => $individualByUnit,
                'pf1seTotalCents' => 0,
                'syndicShareCents' => 0,
                'internetShareCents' => 0,
                'internetChargedToUnitId' => null,
            ];
        }

        $syndicShare = min($syndicShareTotalCents, $pf1seTotal);
        $internetShare = $pf1seTotal - $syndicShare;
        $baseEqual = $mergedEqualCents - $pf1seTotal;
        if ($baseEqual < 0) {
            $baseEqual = 0;
        }

        $individual = $individualByUnit;
        $internetUnitId = null;
        if ($internetShare > 0) {
            $internetUnitId = $this->resolveUnitIdByNumber($residentUnits, self::INTERNET_CHARGED_TO_UNIT);
            if ($internetUnitId !== null) {
                $individual[$internetUnitId] = ($individual[$internetUnitId] ?? 0) + $internetShare;
            }
        }

        return [
            'baseEqualPoolCents' => $baseEqual,
            'syndicEqualPoolCents' => $syndicShare,
            'individualByUnit' => $individual,
            'pf1seTotalCents' => $pf1seTotal,
            'syndicShareCents' => $syndicShare,
            'internetShareCents' => $internetShare,
            'internetChargedToUnitId' => $internetUnitId,
        ];
    }

    /**
     * @param array<int, Expense>          $expenses
     * @param array<int, RecurringExpense> $recurringExpenses
     */
    public function pf1seTotalCentsForMonth(
        array $expenses,
        array $recurringExpenses,
        int $year,
        int $month,
    ): int {
        $total = 0;

        foreach ($expenses as $expense) {
            if (!$expense instanceof Expense) {
                continue;
            }
            if (!$this->isPf1seExpense($expense)) {
                continue;
            }
            $classification = $this->monthlyExpenseAggregator->classifyForSlip($expense);
            if (!$classification['included']) {
                continue;
            }
            $total += $expense->amount();
        }

        foreach ($recurringExpenses as $recurring) {
            if (!$recurring instanceof RecurringExpense) {
                continue;
            }
            $type = $recurring->type();
            if (mb_strtoupper((string) $type->code()) !== self::SYNDIC_EXPENSE_TYPE_CODE) {
                continue;
            }
            if (!$recurring->isActive() || !$recurring->hasPredefinedAmount()) {
                continue;
            }
            if ($this->recurringExpenseSlipContribution->isSupersededByReconciledExpense($recurring, $expenses)) {
                continue;
            }
            $slice = $this->recurringExpenseSlipContribution->contributionForMonth([$recurring], $year, $month, $expenses);
            if ($slice['equal'] > 0 || $slice['fraction'] > 0) {
                $total += $recurring->amount();
            }
        }

        return $total;
    }

    private function isPf1seExpense(Expense $expense): bool
    {
        $type = $expense->type();
        if ($type === null) {
            return false;
        }

        return mb_strtoupper((string) $type->code()) === self::SYNDIC_EXPENSE_TYPE_CODE;
    }

    /**
     * @param array<int, ResidentUnit> $residentUnits
     */
    private function resolveUnitIdByNumber(array $residentUnits, string $unitNumber): ?string
    {
        $needle = trim($unitNumber);
        foreach ($residentUnits as $unit) {
            if (trim((string) $unit->unit()) === $needle) {
                return $unit->id();
            }
        }

        return null;
    }
}
