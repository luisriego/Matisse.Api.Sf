<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\CreateUnitWithRecipients;

use App\Context\ResidentUnit\Domain\Exception\IdealFractionSumExceedsLimitException;
use App\Context\ResidentUnit\Domain\IdealFractionSumPolicy;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitId;
use App\Context\ResidentUnit\Domain\ResidentUnitIdealFraction;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\ResidentUnit\Domain\ResidentUnitVO;
use App\Context\User\Application\UseCase\InviteResident\InviteResidentFromUnitCommand;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Exception\InvalidArgumentException;
use DateMalformedStringException;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class CreateResidentUnitWithRecipientsCommandHandler implements CommandHandler
{
    public function __construct(
        private ResidentUnitRepository $repository,
        private MessageBusInterface $commandBus,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws IdealFractionSumExceedsLimitException
     * @throws DateMalformedStringException
     */
    public function __invoke(CreateResidentUnitWithRecipientsCommand $command): void
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

        $residentUnit = ResidentUnit::createWithRecipients(
            $id,
            $unit,
            $idealFraction,
            $command->notificationRecipients(),
        );

        $this->repository->save($residentUnit, true);

        $this->dispatchInvites($command);
    }

    /**
     * @throws IdealFractionSumExceedsLimitException
     * @throws DateMalformedStringException
     */
    private function updateExisting(
        string $id,
        ResidentUnitVO $unit,
        ResidentUnitIdealFraction $idealFraction,
        CreateResidentUnitWithRecipientsCommand $command,
    ): void {
        $residentUnit = $this->repository->findOneByIdOrFail($id);

        $this->assertIdealFractionWithinLimit(
            $this->repository->calculateTotalIdealFraction($id),
            $idealFraction->value(),
        );

        $residentUnit->updateFromSetup($unit, $idealFraction);
        $residentUnit->replaceRecipients($command->notificationRecipients());
        $this->repository->save($residentUnit, true);

        $this->dispatchInvites($command);
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

    private function dispatchInvites(CreateResidentUnitWithRecipientsCommand $command): void
    {
        foreach ($command->notificationRecipients() as $recipient) {
            if (!isset($recipient['email'])) {
                continue;
            }

            $this->commandBus->dispatch(new InviteResidentFromUnitCommand(
                $command->id(),
                $recipient['email'],
                $recipient['name'] ?? null,
            ));
        }
    }
}
