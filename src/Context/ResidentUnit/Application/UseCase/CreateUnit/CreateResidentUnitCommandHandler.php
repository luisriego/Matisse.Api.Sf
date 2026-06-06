<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\CreateUnit;

use App\Context\ResidentUnit\Domain\Exception\IdealFractionSumExceedsLimitException;
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
     */
    public function __invoke(CreateResidentUnitCommand $command): void
    {
        $id = new ResidentUnitId($command->id());
        $unit = new ResidentUnitVO($command->unit());
        $idealFraction = new ResidentUnitIdealFraction($command->idealFraction());

        if ($this->repository->exists($id)) {
            $this->updateExisting($command->id(), $unit, $idealFraction, $command);

            return;
        }

        $this->assertIdealFractionWithinLimit(
            $this->repository->calculateTotalIdealFraction(),
            $idealFraction->value(),
        );

        $residentUnit = ResidentUnit::create($id, $unit, $idealFraction);

        $this->repository->save($residentUnit, true);

        $this->dispatchInvite($command);
    }

    /**
     * @throws IdealFractionSumExceedsLimitException
     */
    private function updateExisting(
        string $id,
        ResidentUnitVO $unit,
        ResidentUnitIdealFraction $idealFraction,
        CreateResidentUnitCommand $command,
    ): void {
        $residentUnit = $this->repository->findOneByIdOrFail($id);

        $this->assertIdealFractionWithinLimit(
            $this->repository->calculateTotalIdealFraction($id),
            $idealFraction->value(),
        );

        $residentUnit->updateFromSetup($unit, $idealFraction);
        $this->repository->save($residentUnit, true);

        $this->dispatchInvite($command);
    }

    /**
     * @throws IdealFractionSumExceedsLimitException
     */
    private function assertIdealFractionWithinLimit(float $currentTotal, float $newFraction): void
    {
        $idealFractionTotal = $currentTotal + $newFraction;

        if (IdealFractionSumPolicy::exceedsMaximum($idealFractionTotal)) {
            throw IdealFractionSumExceedsLimitException::fromTotals($currentTotal, $newFraction);
        }
    }

    private function dispatchInvite(CreateResidentUnitCommand $command): void
    {
        $this->commandBus->dispatch(new InviteResidentFromUnitCommand(
            $command->id(),
            $command->email(),
            $command->name(),
        ));
    }
}
