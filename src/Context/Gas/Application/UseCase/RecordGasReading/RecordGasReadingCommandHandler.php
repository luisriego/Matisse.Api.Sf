<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\RecordGasReading;

use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Gas\Domain\Bus\GasPriceWasDefined;
use App\Context\Gas\Domain\Gas;
use App\Context\Gas\Domain\ValueObject\GasId;
use App\Context\Gas\Domain\ValueObject\ReadingInM3;
use App\Context\ResidentUnit\Domain\ResidentUnitId;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\ValueObject\Month;
use App\Shared\Domain\ValueObject\Year;
use DateMalformedStringException;

final readonly class RecordGasReadingCommandHandler implements CommandHandler
{
    public function __construct(
        private EventBus $eventBus,
        private StoredEventRepository $storedEventRepository
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

        $priceEvents = $this->storedEventRepository->findByEventType(GasPriceWasDefined::eventName());
        $lastPriceEvent = end($priceEvents);
        $price = null;

        if (false !== $lastPriceEvent) {
            $price = $lastPriceEvent->toPrimitives()['pricePerM3'];
        }

        $gas = Gas::recordReading(
            $id,
            $residentUnitId,
            $year,
            $month,
            $reading,
            $price,
        );

        $events = $gas->pullDomainEvents();

        $this->eventBus->publish(...$events);
    }
}
