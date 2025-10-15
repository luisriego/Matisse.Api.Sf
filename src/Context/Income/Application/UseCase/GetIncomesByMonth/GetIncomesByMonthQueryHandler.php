<?php

declare(strict_types=1);

namespace App\Context\Income\Application\UseCase\GetIncomesByMonth;

use App\Context\Income\Domain\Income;
use App\Context\Income\Domain\IncomeRepository;
use App\Shared\Application\QueryHandler;
use App\Shared\Domain\ValueObject\DateRange;

final readonly class GetIncomesByMonthQueryHandler implements QueryHandler
{
    public function __construct(private IncomeRepository $incomeRepository)
    {}

    public function __invoke(GetIncomesByMonthQuery $query): array
    {
        $dateRange = DateRange::createFromMonthAndYear($query->month(), $query->year());

        $incomes = $this->incomeRepository->findActiveByDateRange($dateRange);

        return array_map(fn(Income $income) => $income->toArray(), $incomes);
    }
}
