<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\FindTypes;

use App\Context\Expense\Domain\ExpenseType;
use App\Context\Expense\Domain\ExpenseTypeRepository;
use App\Shared\Application\QueryHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use function array_map;

#[AsMessageHandler(bus: 'query.bus')]
readonly class FindTypesQueryHandler implements QueryHandler
{
    public function __construct(private ExpenseTypeRepository $repository) {}

    public function __invoke(FindTypesQuery $query): array
    {
        $expenseTypes = $this->repository->findAll();

        return array_map(static fn (ExpenseType $expenseType) => $expenseType->toArray(), $expenseTypes);
    }
}
