<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\ListAll;

use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Shared\Application\QueryHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListAllResidentUnitsQueryHandler implements QueryHandler
{
    public function __construct(
        private ResidentUnitRepository $residentUnitRepository,
    ) {}

    public function __invoke(ListAllResidentUnitsQuery $query): array
    {
        return $this->residentUnitRepository->findAll();
    }
}
