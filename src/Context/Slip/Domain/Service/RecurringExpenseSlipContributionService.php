<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Service;

use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\RecurringExpense;
use DateTimeImmutable;
use DateTimeInterface;

use function in_array;
use function mb_strtoupper;
use function sprintf;

/**
 * Suma montos de plantillas recurrentes aplicables al mes de liquidación.
 * INDIVIDUAL (p. ej. SP3GA/gás) se omite: los boletos cargan gás vía eventos del mes anterior.
 *
 * Anti-doble-conteo: si ya existe un Expense conciliado del mes (mismo recurring o mismo tipo),
 * la plantilla fija no suma — el gasto real prevalece.
 */
readonly class RecurringExpenseSlipContributionService
{
    private const string GAS_EXPENSE_TYPE_CODE = 'SP3GA';
    private const string WATER_EXPENSE_TYPE_CODE = 'SP2AG';

    /**
     * @param array<int, RecurringExpense> $recurringExpenses
     * @param array<int, Expense>          $reconciledExpenses gastos reales del mes (conciliación OFX / enter)
     *
     * @return array{equal: int, fraction: int}
     */
    public function contributionForMonth(
        array $recurringExpenses,
        int $year,
        int $month,
        array $reconciledExpenses = [],
    ): array {
        $equal = 0;
        $fraction = 0;

        foreach ($recurringExpenses as $recurring) {
            if (!$recurring instanceof RecurringExpense) {
                continue;
            }

            if (!$recurring->isActive() || !$recurring->hasPredefinedAmount()) {
                continue;
            }

            if ($this->isSupersededByReconciledExpense($recurring, $reconciledExpenses)) {
                continue;
            }

            if (!$this->appliesToCalendarMonth($recurring, $year, $month)) {
                continue;
            }

            $type = $recurring->type();
            $method = mb_strtoupper((string) $type->distributionMethod());
            $code = mb_strtoupper((string) $type->code());

            if ($code === self::GAS_EXPENSE_TYPE_CODE || $method === ExpenseType::INDIVIDUAL) {
                continue;
            }

            if ($code === self::WATER_EXPENSE_TYPE_CODE) {
                $method = ExpenseType::FRACTION;
            } else {
                $method = ExpenseType::EQUAL;
            }

            $amount = $recurring->amount();

            if ($method === ExpenseType::EQUAL) {
                $equal += $amount;
            } elseif ($method === ExpenseType::FRACTION) {
                $fraction += $amount;
            }
        }

        return [
            'equal' => $equal,
            'fraction' => $fraction,
        ];
    }

    /**
     * @param array<int, Expense> $reconciledExpenses
     */
    public function isSupersededByReconciledExpense(RecurringExpense $recurring, array $reconciledExpenses): bool
    {
        if ($reconciledExpenses === []) {
            return false;
        }

        $recurringId = $recurring->id();
        $typeId = $recurring->type()->id();

        foreach ($reconciledExpenses as $expense) {
            if (!$expense instanceof Expense || !$expense->isActive()) {
                continue;
            }

            $expenseType = $expense->type();

            if ($expenseType === null) {
                continue;
            }

            $linked = $expense->recurringExpense();

            if ($linked !== null && $linked->id() === $recurringId) {
                return true;
            }

            if ($typeId !== null && $expenseType->id() === $typeId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Forecast projection: includes variable recurring expenses (hasPredefinedAmount=false).
     *
     * @param array<int, RecurringExpense> $recurringExpenses
     *
     * @return array{equal: int, fraction: int}
     */
    public function forecastContributionForMonth(array $recurringExpenses, int $year, int $month): array
    {
        $equal = 0;
        $fraction = 0;

        foreach ($recurringExpenses as $recurring) {
            if (!$recurring instanceof RecurringExpense) {
                continue;
            }

            if (!$recurring->isActive()) {
                continue;
            }

            if (!$this->appliesToCalendarMonth($recurring, $year, $month)) {
                continue;
            }

            $type = $recurring->type();
            $method = mb_strtoupper((string) $type->distributionMethod());
            $code = mb_strtoupper((string) $type->code());

            if ($code === self::GAS_EXPENSE_TYPE_CODE || $method === ExpenseType::INDIVIDUAL) {
                continue;
            }

            if ($code === self::WATER_EXPENSE_TYPE_CODE) {
                $method = ExpenseType::FRACTION;
            } else {
                $method = ExpenseType::EQUAL;
            }

            $amount = $recurring->amount();

            if ($method === ExpenseType::EQUAL) {
                $equal += $amount;
            } elseif ($method === ExpenseType::FRACTION) {
                $fraction += $amount;
            }
        }

        return [
            'equal' => $equal,
            'fraction' => $fraction,
        ];
    }

    private function appliesToCalendarMonth(RecurringExpense $recurring, int $year, int $month): bool
    {
        $monthStart = new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $monthEnd = $monthStart->modify('last day of this month')->setTime(23, 59, 59);

        $start = $this->toImmutableStartOfDay($recurring->startDate());

        if ($start > $monthEnd) {
            return false;
        }

        $end = $recurring->endDate();

        if ($end !== null && $this->toImmutableStartOfDay($end) < $monthStart) {
            return false;
        }

        $monthsOfYear = $recurring->monthsOfYear();

        if ($monthsOfYear !== null && $monthsOfYear !== []) {
            $normalized = [];

            foreach ($monthsOfYear as $m) {
                $normalized[] = (int) $m;
            }

            if (!in_array($month, $normalized, true)) {
                return false;
            }
        }

        return true;
    }

    private function toImmutableStartOfDay(DateTimeInterface $date): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0);
    }
}
