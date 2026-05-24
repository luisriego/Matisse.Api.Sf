<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\SetGasPrice;

use App\Context\Gas\Domain\Gas;
use App\Shared\Application\CommandHandler;
use App\Shared\Application\EventStore;
use App\Shared\Domain\Event\EventBus;

final readonly class SetGasPriceCommandHandler implements CommandHandler
{
    public function __construct(
        private EventBus   $eventBus,
        private EventStore $eventStore,
    ) {}

    /**
     * @throws \DateMalformedStringException
     */
    public function __invoke(SetGasPriceCommand $command): void
    {
        $gas = Gas::setDirectPrice($command->pricePerM3InCents);

        foreach ($gas->pullDomainEvents() as $event) {
            $this->eventStore->append($event);
            $this->eventBus->publish($event);
        }
    }
}
