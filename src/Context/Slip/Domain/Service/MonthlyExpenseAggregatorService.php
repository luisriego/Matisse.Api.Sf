<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Service;

use App\Context\Expense\Domain\Expense;
use Psr\Log\LoggerInterface;

use function get_debug_type;
use function mb_strtoupper;
use function sprintf;

readonly class MonthlyExpenseAggregatorService
{
    private const string GAS_EXPENSE_TYPE_CODE = 'SP3GA';
    private const string WATER_EXPENSE_TYPE_CODE = 'SP2AG';

    public function __construct(private LoggerInterface $logger) {}

    /**
     * Agrega los gastos mensuales por tipo de distribución.
     *
     * @param array<Expense> $monthlyExpenses
     *
     * @return array{
     *     equal: int,
     *     fraction: int,
     *     individual: int,
     *     individualByUnit: array<string, int>,
     *     grandTotal: int
     * }
     */
    public function aggregateTotals(array $monthlyExpenses): array
    {
        $totals = [
            'equal' => 0,
            'fraction' => 0,
            'individual' => 0,
            'individualByUnit' => [],
            'grandTotal' => 0,
        ];

        if (empty($monthlyExpenses)) {
            return $totals;
        }

        foreach ($monthlyExpenses as $expense) {
            if (!$expense instanceof Expense) {
                $this->logger->warning(sprintf('[MonthlyExpenseAggregator] Elemento inesperado en monthlyExpenses, se esperaba App\Entity\Expense, se obtuvo %s.', get_debug_type($expense)));

                continue;
            }

            $expenseType = $expense->type();

            if (!$expenseType) {
                $errorMessage = sprintf(
                    '[MonthlyExpenseAggregator] Despesa "%s" (ID: %s) não tem um tipo registrado. Não será contabilizada nos totais.',
                    $expense->description() ?? 'N/D',
                    $expense->id(),
                );
                $this->logger->error($errorMessage);

                // Podrías decidir lanzar una excepción aquí si es un error crítico
                // throw new \RuntimeException($errorMessage);
                continue; // O simplemente omitirla de los totales
            }

            $amount = $expense->amount();
            $totals['grandTotal'] += $amount;
            $classification = $this->classifyForSlip($expense);
            if (!$classification['included']) {
                continue;
            }

            $method = $classification['bucket'];

            switch ($method) {
                case 'EQUAL':
                    $totals['equal'] += $amount;
                    break;
                case 'FRACTION':
                    $totals['fraction'] += $amount;
                    break;
                case 'INDIVIDUAL':
                    $totals['individual'] += $amount;
                    $unitId = $expense->residentUnitId();
                    if ($unitId !== null) {
                        $totals['individualByUnit'][$unitId] = ($totals['individualByUnit'][$unitId] ?? 0) + $amount;
                    } else {
                        $this->logger->warning(sprintf(
                            '[MonthlyExpenseAggregator] Gasto INDIVIDUAL sin residentUnitId (expense %s). No se asignará a ninguna unidad en el boleto.',
                            $expense->id(),
                        ));
                    }
                    break;
                default:
                    $totals['individual'] += $amount;
                    $this->logger->warning(sprintf(
                        '[MonthlyExpenseAggregator] Método de distribución no reconocido "%s" en gasto %s; se trata como individual agregado sin unidad.',
                        $method,
                        $expense->id(),
                    ));
                    break;
            }
        }

        return $totals;
    }

    /**
     * @return array{included: bool, bucket: string, reason: string}
     */
    public function classifyForSlip(Expense $expense): array
    {
        $expenseType = $expense->type();
        if ($expenseType === null) {
            return [
                'included' => false,
                'bucket' => 'excluded',
                'reason' => 'missing_expense_type',
            ];
        }

        $code = mb_strtoupper((string) $expenseType->code());
        if ($code === self::GAS_EXPENSE_TYPE_CODE) {
            return [
                'included' => false,
                'bucket' => 'excluded',
                'reason' => 'gas_settled_by_consumption',
            ];
        }

        if ($code === self::WATER_EXPENSE_TYPE_CODE) {
            return [
                'included' => true,
                'bucket' => 'FRACTION',
                'reason' => 'water_by_fraction_rule',
            ];
        }

        return [
            'included' => true,
            'bucket' => 'EQUAL',
            'reason' => 'default_equal_rule',
        ];
    }
}
