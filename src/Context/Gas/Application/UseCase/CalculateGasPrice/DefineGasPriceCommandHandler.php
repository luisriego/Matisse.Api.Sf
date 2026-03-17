<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\CalculateGasPrice;

use App\Context\Gas\Domain\Gas;
use App\Context\Gas\Domain\ValueObject\BufferPercentage;
use App\Context\Gas\Domain\ValueObject\CylinderCapacity;
use App\Context\Gas\Domain\ValueObject\GasAmount;
use App\Shared\Application\CommandHandler;
use App\Shared\Application\EventStore;
use App\Shared\Domain\Event\EventBus;
use DateMalformedStringException;

final class DefineGasPriceCommandHandler implements CommandHandler
{
    private const int DEFAULT_CYLINDER_CAPACITY_KG = 45;
    private const int DEFAULT_BUFFER_PERCENTAGE = 10;

    public function __construct(
        private readonly EventBus $eventBus,
        private readonly EventStore $eventStore
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(DefineGasPriceCommand $command): void
    {
        $amount = new GasAmount($command->getBillAmountInCents());
        $capacityInKg = $command->getCylinderCapacityInKg() ?? self::DEFAULT_CYLINDER_CAPACITY_KG;
        $capacity = new CylinderCapacity($capacityInKg);
        $bufferPercentageValue = $command->getBufferPercentage() ?? self::DEFAULT_BUFFER_PERCENTAGE;
        $buffer = new BufferPercentage($bufferPercentageValue);

        $gas = Gas::definePrice($amount, $capacity, $buffer);
        foreach ($gas->pullDomainEvents() as $event) {
            $this->eventStore->append($event);
            $this->eventBus->publish($event);
        }
    }
}
