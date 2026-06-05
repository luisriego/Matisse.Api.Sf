<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\CreateUnitWithRecipients;

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
use App\Shared\Domain\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class CreateResidentUnitWithRecipientsCommandHandler implements CommandHandler
{
    public function __construct(
        private ResidentUnitRepository $repository,
        private MessageBusInterface $commandBus,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws ResidentUnitAlreadyExistsException
     * @throws IdealFractionSumExceedsLimitException
     */
    public function __invoke(CreateResidentUnitWithRecipientsCommand $command): void
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

        $residentUnit = ResidentUnit::createWithRecipients(
            $id,
            $unit,
            $idealFraction,
            $command->notificationRecipients(),
        );

        $this->repository->save($residentUnit, true);

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
