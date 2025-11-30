<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\CreateUnitWithRecipients;

use App\Context\ResidentUnit\Application\Message\WelcomeResidentNotification;
use App\Context\ResidentUnit\Domain\Exception\IdealFractionSumExceedsLimitException; // Added this import
use App\Context\ResidentUnit\Domain\Exception\ResidentUnitAlreadyExistsException;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitId;
use App\Context\ResidentUnit\Domain\ResidentUnitIdealFraction;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\ResidentUnit\Domain\ResidentUnitVO;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class CreateResidentUnitWithRecipientsCommandHandler implements CommandHandler
{
    public function __construct(
        private ResidentUnitRepository $repository,
        private MessageBusInterface $bus,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws ResidentUnitAlreadyExistsException
     * @throws IdealFractionSumExceedsLimitException // Added this throw annotation
     */
    public function __invoke(CreateResidentUnitWithRecipientsCommand $command): void
    {
        // Check if a resident unit with this ID already exists
        if ($this->repository->exists(new ResidentUnitId($command->id()))) {
            throw ResidentUnitAlreadyExistsException::create($command->id());
        }

        $idealFraction = new ResidentUnitIdealFraction($command->idealFraction());
        $idealFractionTotal = $this->repository->calculateTotalIdealFraction() + $idealFraction->value();

        if ($idealFractionTotal > 1.0) {
            throw new IdealFractionSumExceedsLimitException(); // Changed to throw the specific exception
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
            if (isset($recipient['name'], $recipient['email'])) {
                $this->bus->dispatch(new WelcomeResidentNotification(
                    $recipient['name'],
                    $recipient['email'],
                    $command->unit(),
                ));
            }
        }
    }
}
