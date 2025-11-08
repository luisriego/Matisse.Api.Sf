<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\UseCase\FindTypes;

use App\Context\Expense\Domain\ExpenseTypeRepository;
use App\Shared\Application\QueryHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;

#[AsMessageHandler(bus: 'query.bus')]
readonly class FindTypesQueryHandler implements QueryHandler
{
    public function __construct(
        private ExpenseTypeRepository $repository,
        private SerializerInterface $serializer,
    ) {}

    public function __invoke(FindTypesQuery $query): array
    {
        $expenseTypes = $this->repository->findAll();

        return $this->serializer->normalize($expenseTypes);
    }
}
