<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\CreateUnit;

use App\Context\ResidentUnit\Domain\Exception\IdealFractionSumExceedsLimitException;
use App\Context\ResidentUnit\Domain\Exception\ResidentUnitAlreadyExistsException;
use App\Context\ResidentUnit\Domain\IdealFractionSumPolicy;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitId;
use App\Context\ResidentUnit\Domain\ResidentUnitIdealFraction;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\ResidentUnit\Domain\ResidentUnitVO; // Corrected this line
use App\Shared\Application\CommandHandler;

final readonly class CreateResidentUnitCommandHandler implements CommandHandler
{
    public function __construct(private ResidentUnitRepository $repository) {}

    /**
     * @throws IdealFractionSumExceedsLimitException
     * @throws ResidentUnitAlreadyExistsException
     */
    public function __invoke(CreateResidentUnitCommand $command): void
    {
        // Check if a resident unit with this ID already exists
        if ($this->repository->exists(new ResidentUnitId($command->id()))) {
            throw ResidentUnitAlreadyExistsException::create($command->id());
        }

        $idealFraction = new ResidentUnitIdealFraction($command->idealFraction());
        $currentActiveTotal = $this->repository->calculateTotalIdealFraction();
        $idealFractionTotal = $currentActiveTotal + $idealFraction->value();

        if (IdealFractionSumPolicy::exceedsMaximum($idealFractionTotal)) {
            throw IdealFractionSumExceedsLimitException::fromTotals($currentActiveTotal, $idealFraction->value());
        }

        $id = new ResidentUnitId($command->id());
        $unit = new ResidentUnitVO($command->unit());

        $residentUnit = ResidentUnit::create($id, $unit, $idealFraction);

        $this->repository->save($residentUnit, true);
    }
}
