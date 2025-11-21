<?php

declare(strict_types=1);

namespace App\Context\Income\Application\UseCase\GetIncomeTypes;

use App\Context\Income\Domain\IncomeTypeRepository;
use App\Shared\Application\QueryHandler;

final readonly class GetIncomeTypesQueryHandler implements QueryHandler
{
    public function __construct(private IncomeTypeRepository $repository) {}

    public function __invoke(GetIncomeTypesQuery $query): array
    {
        $incomeTypes = $this->repository->findAll();

        $response = [];

        foreach ($incomeTypes as $type) {
            $response[] = [
                'id' => $type->id(),
                'name' => $type->name(),
                'code' => $type->code(),
                'description' => $type->description(),
            ];
        }

        return $response;
    }
}
