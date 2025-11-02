<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\RecordGasConsumption;

use App\Context\Gas\Domain\Gas;
use App\Context\Gas\Domain\ValueObject\ConsumptionInM3;
use App\Context\Gas\Domain\ValueObject\GasId;
use App\Context\ResidentUnit\Domain\ResidentUnitId;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Event\EventBus;

final readonly class RecordGasConsumptionCommandHandler implements CommandHandler
{
    public function __construct(private EventBus $eventBus)
    {
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function __invoke(RecordGasConsumptionCommand $command): void
    {
        $id = new GasId($command->getId());
        $residentUnitId = new ResidentUnitId($command->getResidentUnitId());
        $consumption = new ConsumptionInM3($command->getConsumption());

        $gas = Gas::recordConsumption(
            $id,
            $residentUnitId,
            $command->getYear(),
            $command->getMonth(),
            $consumption
        );

        $events = $gas->pullDomainEvents();

        $this->eventBus->publish(...$events);
    }
}