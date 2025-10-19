<?php

declare(strict_types=1);

namespace App\Context\Income\Application\UseCase\GetIncomesByMonth;

use App\Context\Income\Domain\Income;
use App\Context\Income\Domain\IncomeRepository;
use App\Shared\Application\QueryHandler;
use App\Shared\Domain\ValueObject\DateRange;
use DateMalformedStringException;

use function array_map;

final readonly class GetIncomesByMonthQueryHandler implements QueryHandler
{
    public function __construct(private IncomeRepository $incomeRepository) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(GetIncomesByMonthQuery $query): array
    {
        // Corrected method call to use the existing `fromMonth` method with correct argument order
        $dateRange = DateRange::fromMonth($query->year(), $query->month());

        $incomes = $this->incomeRepository->findActiveByDateRange($dateRange);

        return array_map(fn (Income $income) => $income->toArray(), $incomes);
    }
}
