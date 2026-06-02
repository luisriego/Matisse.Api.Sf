<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\PatchIdealFraction;

use App\Context\ResidentUnit\Domain\Exception\IdealFractionSumExceedsLimitException;
use App\Context\ResidentUnit\Domain\IdealFractionSumPolicy;
use App\Context\ResidentUnit\Domain\ResidentUnitIdealFraction;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Shared\Application\CommandHandler;

final readonly class PatchIdealFractionCommandHandler implements CommandHandler
{
    public function __construct(private ResidentUnitRepository $repository) {}

    public function __invoke(PatchIdealFractionCommand $command): void
    {
        $residentUnit = $this->repository->findOneByIdOrFail($command->id);

        $idealFraction = new ResidentUnitIdealFraction($command->idealFraction);

        $totalIdealFraction = $this->repository->calculateTotalIdealFraction($command->id);
        $projectedTotal = $totalIdealFraction + $idealFraction->value();

        if (IdealFractionSumPolicy::exceedsMaximum($projectedTotal)) {
            throw IdealFractionSumExceedsLimitException::fromTotals($totalIdealFraction, $idealFraction->value());
        }

        $residentUnit->changeIdealFraction($idealFraction);

        $this->repository->save($residentUnit);
    }
}
