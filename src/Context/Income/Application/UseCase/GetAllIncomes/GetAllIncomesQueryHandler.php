<?php

declare(strict_types=1);

namespace App\Context\Income\Application\UseCase\GetAllIncomes;

use App\Context\Income\Domain\Income;
use App\Context\Income\Domain\IncomeRepository;
use App\Shared\Application\QueryHandler;

use function array_map;

final readonly class GetAllIncomesQueryHandler implements QueryHandler
{
    public function __construct(private IncomeRepository $incomeRepository) {}

    public function __invoke(GetAllIncomesQuery $query): array
    {
        $incomes = $this->incomeRepository->findAll();

        return array_map(fn (Income $income) => $income->toArray(), $incomes);
    }
}
