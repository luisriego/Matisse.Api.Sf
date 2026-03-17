<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\RecordGasReading;

use App\Context\Gas\Domain\Gas;
use App\Context\Gas\Domain\ValueObject\GasId;
use App\Context\Gas\Domain\ValueObject\ReadingInM3;
use App\Context\ResidentUnit\Domain\ResidentUnitId;
use App\Shared\Application\CommandHandler;
use App\Shared\Application\EventStore;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\ValueObject\Month;
use App\Shared\Domain\ValueObject\Year;
use DateMalformedStringException;

final readonly class RecordGasReadingCommandHandler implements CommandHandler
{
    public function __construct(
        private EventBus $eventBus,
        private EventStore $eventStore
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(RecordGasReadingCommand $command): void
    {
        $id = new GasId($command->id());
        $residentUnitId = new ResidentUnitId($command->residentUnitId());
        $year = new Year($command->year());
        $month = new Month($command->month());
        $reading = new ReadingInM3($command->reading());

        $gas = Gas::recordReading(
            $id,
            $residentUnitId,
            $year,
            $month,
            $reading,
        );

        foreach ($gas->pullDomainEvents() as $event) {
            $this->eventStore->append($event);
            $this->eventBus->publish($event);
        }
    }
}
