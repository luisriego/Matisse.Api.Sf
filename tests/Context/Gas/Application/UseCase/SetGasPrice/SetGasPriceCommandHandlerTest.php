<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Application\UseCase\SetGasPrice;

use App\Context\Gas\Application\UseCase\SetGasPrice\SetGasPriceCommand;
use App\Context\Gas\Application\UseCase\SetGasPrice\SetGasPriceCommandHandler;
use App\Context\Gas\Domain\Event\GasPriceWasDefined;
use App\Shared\Application\EventStore;
use App\Shared\Domain\Event\EventBus;
use DateMalformedStringException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SetGasPriceCommandHandlerTest extends TestCase
{
    private EventBus&MockObject $eventBus;
    private EventStore&MockObject $eventStore;
    private SetGasPriceCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventBus   = $this->createMock(EventBus::class);
        $this->eventStore = $this->createMock(EventStore::class);
        $this->handler    = new SetGasPriceCommandHandler($this->eventBus, $this->eventStore);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testItShouldAppendAndPublishGasPriceWasDefined(): void
    {
        $pricePerM3InCents = 2600;

        $this->eventStore->expects(self::once())->method('append')->with(
            self::callback(static function ($event) use ($pricePerM3InCents): bool {
                return $event instanceof GasPriceWasDefined
                    && $pricePerM3InCents === $event->pricePerM3InCents;
            }),
        );

        $this->eventBus->expects(self::once())->method('publish')->with(
            self::callback(static function ($event) use ($pricePerM3InCents): bool {
                return $event instanceof GasPriceWasDefined
                    && $pricePerM3InCents === $event->pricePerM3InCents;
            }),
        );

        ($this->handler)(new SetGasPriceCommand($pricePerM3InCents));
    }

    public function testItShouldPropagateExceptionFromEventBus(): void
    {
        $this->eventStore->expects(self::once())->method('append');
        $this->eventBus
            ->expects(self::once())
            ->method('publish')
            ->willThrowException(new RuntimeException('Event bus failure'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Event bus failure');

        ($this->handler)(new SetGasPriceCommand(100));
    }
}
