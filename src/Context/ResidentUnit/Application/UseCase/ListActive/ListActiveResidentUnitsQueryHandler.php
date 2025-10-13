<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\ListActive;

use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Shared\Application\QueryHandler;

final readonly class ListActiveResidentUnitsQueryHandler implements QueryHandler
{
    public function __construct(
        private ResidentUnitRepository $residentUnitRepository,
    ) {}

    public function __invoke(ListActiveResidentUnitsQuery $query): array
    {
        return $this->residentUnitRepository->findAllActive();
    }
}
