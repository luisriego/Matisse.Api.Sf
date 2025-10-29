<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\FindResidentUnitById;

use App\Context\ResidentUnit\Application\Response\ResidentUnitResponse;
use App\Context\ResidentUnit\Application\Response\ResidentUnitResponseConverter;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Shared\Application\QueryHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class FindResidentUnitByIdQueryHandler implements QueryHandler
{
    public function __construct(
        private ResidentUnitRepository $repository,
        private ResidentUnitResponseConverter $converter
    ) {
    }

    public function __invoke(FindResidentUnitByIdQuery $query): ResidentUnitResponse
    {
        $residentUnitId = $query->id();
        $residentUnit = $this->repository->findOneByIdOrFail($residentUnitId);

        return ($this->converter)($residentUnit);
    }
}
