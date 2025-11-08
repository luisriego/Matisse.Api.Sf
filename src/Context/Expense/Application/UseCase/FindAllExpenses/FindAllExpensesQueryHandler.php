<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\FindAllExpenses;

use App\Context\Expense\Domain\ExpenseRepository;
use App\Shared\Application\QueryHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;

use function count;

#[AsMessageHandler(bus: 'query.bus')]
readonly class FindAllExpensesQueryHandler implements QueryHandler
{
    public function __construct(
        private ExpenseRepository $repository,
        private SerializerInterface $serializer,
    ) {}

    public function __invoke(FindAllExpensesQuery $query): array
    {
        $expenses = $this->repository->findAll();

        $expensesArray = $this->serializer->normalize($expenses);

        return [
            'expenses' => $expensesArray,
            'qtd' => count($expensesArray),
        ];
    }
}
