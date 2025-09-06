<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\CreateUnitWithRecipients;

use App\Context\ResidentUnit\Application\Message\WelcomeResidentNotification;
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
     */
    public function __invoke(CreateResidentUnitWithRecipientsCommand $command): void
    {
        $idealFraction = new ResidentUnitIdealFraction($command->idealFraction());
        $idealFractionTotal = $this->repository->calculateTotalIdealFraction() + $idealFraction->value();

        if ($idealFractionTotal > 1.0) {
            throw new InvalidArgumentException('Ideal fraction must not be more than 1');
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
