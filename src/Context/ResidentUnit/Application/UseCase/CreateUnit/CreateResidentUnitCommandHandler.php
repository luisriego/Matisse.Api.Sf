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
use App\Context\ResidentUnit\Domain\ResidentUnitVO;
use App\Context\User\Application\UseCase\InviteResident\InviteResidentFromUnitCommand;
use App\Shared\Application\CommandHandler;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class CreateResidentUnitCommandHandler implements CommandHandler
{
    public function __construct(
        private ResidentUnitRepository $repository,
        private MessageBusInterface $commandBus,
    ) {}

    /**
     * @throws IdealFractionSumExceedsLimitException
     * @throws ResidentUnitAlreadyExistsException
     */
    public function __invoke(CreateResidentUnitCommand $command): void
    {
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

        $this->commandBus->dispatch(new InviteResidentFromUnitCommand(
            $command->id(),
            $command->email(),
            $command->name(),
        ));
    }
}
