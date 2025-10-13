<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\FindResidentUnitById;

use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Shared\Application\QueryHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class FindResidentUnitByIdQueryHandler implements QueryHandler
{
    public function __construct(private ResidentUnitRepository $repository) {}

    public function __invoke(FindResidentUnitByIdQuery $query): array
    {
        $residentUnitId = $query->id();
        $residentUnit = $this->repository->findOneByIdOrFail($residentUnitId);

        return $residentUnit->toArray();
    }
}
