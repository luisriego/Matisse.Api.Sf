<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\CalculateGasPrice;

use App\Context\Gas\Domain\Gas;
use App\Context\Gas\Domain\ValueObject\BufferPercentage;
use App\Context\Gas\Domain\ValueObject\CylinderCapacity;
use App\Context\Gas\Domain\ValueObject\GasAmount;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Event\EventBus;

final class DefineGasPriceCommandHandler implements CommandHandler
{
    private const DEFAULT_CYLINDER_CAPACITY_KG = 45;
    private const DEFAULT_BUFFER_PERCENTAGE = 10;

    public function __construct(private readonly EventBus $eventBus) {}

    public function __invoke(DefineGasPriceCommand $command): void
    {
        $amount = new GasAmount($command->getBillAmountInCents());
        $capacityInKg = $command->getCylinderCapacityInKg() ?? self::DEFAULT_CYLINDER_CAPACITY_KG;
        $capacity = new CylinderCapacity($capacityInKg);
        $bufferPercentageValue = $command->getBufferPercentage() ?? self::DEFAULT_BUFFER_PERCENTAGE;
        $buffer = new BufferPercentage($bufferPercentageValue);

        $gas = Gas::definePrice($amount, $capacity, $buffer);

        $events = $gas->pullDomainEvents();

        $this->eventBus->publish(...$events);
    }
}
