<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\ExplainSlipGeneration;

use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Slip\Domain\Service\SlipGenerationBreakdownBuilder;
use App\Shared\Application\QueryHandler;
use App\Shared\Domain\ValueObject\DateRange;
use DateMalformedStringException;

final readonly class ExplainSlipGenerationQueryHandler implements QueryHandler
{
    public function __construct(
        private ExpenseRepository $expenseRepository,
        private RecurringExpenseRepository $recurringExpenseRepository,
        private ResidentUnitRepository $residentUnitRepository,
        private SlipGenerationBreakdownBuilder $slipGenerationBreakdownBuilder,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws DateMalformedStringException
     */
    public function __invoke(ExplainSlipGenerationQuery $query): array
    {
        $range = DateRange::fromMonth($query->year(), $query->month());
        $expenses = $this->expenseRepository->findActiveByDateRange($range);
        $recurringExpenses = $this->recurringExpenseRepository->findActiveForDateRange($range);
        $residentUnits = $this->residentUnitRepository->findAllActive();

        if ($residentUnits === []) {
            return [
                'targetMonth' => sprintf('%04d-%02d', $query->year(), $query->month()),
                'error' => 'no_active_resident_units',
                'message' => 'No hay unidades residenciales activas; no se puede calcular el desglose.',
            ];
        }

        return $this->slipGenerationBreakdownBuilder->build(
            $expenses,
            $recurringExpenses,
            $residentUnits,
            $query->year(),
            $query->month(),
            $query->extraFeePerUnitCents(),
            $query->reserveFundPerUnitCents(),
        );
    }
}
