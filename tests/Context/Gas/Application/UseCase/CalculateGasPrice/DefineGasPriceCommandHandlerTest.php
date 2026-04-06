<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Application\UseCase\CalculateGasPrice;

use App\Context\Gas\Application\UseCase\CalculateGasPrice\DefineGasPriceCommandHandler;
use App\Context\Gas\Domain\Event\GasPriceWasDefined;
use App\Shared\Application\EventStore;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\Exception\InvalidArgumentException;
use DateMalformedStringException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function round;

class DefineGasPriceCommandHandlerTest extends TestCase
{
    private EventBus&MockObject $eventBus;
    private EventStore&MockObject $eventStore;
    private DefineGasPriceCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventBus = $this->createMock(EventBus::class);
        $this->eventStore = $this->createMock(EventStore::class);
        $this->handler = new DefineGasPriceCommandHandler($this->eventBus, $this->eventStore);
    }

    private static function expectedPricePerM3InCents(int $billCents, int $kg, int $bufferPct): int
    {
        return (int) round((2 * $billCents * (100 + $bufferPct)) / ($kg * 100));
    }

    /**
     * @throws DateMalformedStringException
     */
    public function test_it_should_define_gas_price_and_publish_event(): void
    {
        $billAmountInCents = 10000;
        $cylinderCapacityInKg = 45;
        $bufferPercentage = 10;

        $command = DefineGasPriceCommandMother::create(
            $billAmountInCents,
            $cylinderCapacityInKg,
            $bufferPercentage
        );

        $expectedPricePerM3InCents = self::expectedPricePerM3InCents($billAmountInCents, $cylinderCapacityInKg, $bufferPercentage);

        $this->eventStore->expects(self::once())->method('append');
        $this->eventBus
            ->expects(self::once())
            ->method('publish')
            ->with($this->callback(function (GasPriceWasDefined $event) use ($expectedPricePerM3InCents) {
                $this->assertNotNull($event->aggregateId());
                $this->assertSame($expectedPricePerM3InCents, $event->pricePerM3InCents);
                return true;
            }));

        ($this->handler)($command);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function test_it_should_define_gas_price_with_default_values_and_publish_event(): void
    {
        $command = DefineGasPriceCommandMother::create(billAmountInCents: 10000, cylinderCapacityInKg: null, bufferPercentage: null);

        $expectedPricePerM3InCents = self::expectedPricePerM3InCents(10000, 45, 10);

        $this->eventStore->expects(self::once())->method('append');
        $this->eventBus
            ->expects(self::once())
            ->method('publish')
            ->with($this->callback(function (GasPriceWasDefined $event) use ($expectedPricePerM3InCents) {
                $this->assertNotNull($event->aggregateId());
                $this->assertSame($expectedPricePerM3InCents, $event->pricePerM3InCents);
                return true;
            }));

        ($this->handler)($command);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function test_it_should_define_gas_price_with_zero_buffer_percentage(): void
    {
        $billAmountInCents = 10000;
        $cylinderCapacityInKg = 45;
        $bufferPercentage = 0;

        $command = DefineGasPriceCommandMother::create(
            $billAmountInCents,
            $cylinderCapacityInKg,
            $bufferPercentage
        );

        $expectedPricePerM3InCents = self::expectedPricePerM3InCents($billAmountInCents, $cylinderCapacityInKg, $bufferPercentage);

        $this->eventStore->expects(self::once())->method('append');
        $this->eventBus
            ->expects(self::once())
            ->method('publish')
            ->with($this->callback(function (GasPriceWasDefined $event) use ($expectedPricePerM3InCents) {
                $this->assertNotNull($event->aggregateId());
                $this->assertSame($expectedPricePerM3InCents, $event->pricePerM3InCents);
                return true;
            }));

        ($this->handler)($command);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function test_it_should_define_gas_price_with_max_buffer_percentage(): void
    {
        $billAmountInCents = 10000;
        $cylinderCapacityInKg = 45;
        $bufferPercentage = 100;

        $command = DefineGasPriceCommandMother::create(
            $billAmountInCents,
            $cylinderCapacityInKg,
            $bufferPercentage
        );

        $expectedPricePerM3InCents = self::expectedPricePerM3InCents($billAmountInCents, $cylinderCapacityInKg, $bufferPercentage);

        $this->eventStore->expects(self::once())->method('append');
        $this->eventBus
            ->expects(self::once())
            ->method('publish')
            ->with($this->callback(function (GasPriceWasDefined $event) use ($expectedPricePerM3InCents) {
                $this->assertNotNull($event->aggregateId());
                $this->assertSame($expectedPricePerM3InCents, $event->pricePerM3InCents);
                return true;
            }));

        ($this->handler)($command);
    }

    public function test_it_should_throw_exception_for_invalid_bill_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $command = DefineGasPriceCommandMother::create(billAmountInCents: -100);
        ($this->handler)($command);
    }

    public function test_it_should_throw_exception_for_invalid_cylinder_capacity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $command = DefineGasPriceCommandMother::create(cylinderCapacityInKg: -10);
        ($this->handler)($command);
    }

    public function test_it_should_throw_exception_for_invalid_buffer_percentage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $command = DefineGasPriceCommandMother::create(bufferPercentage: -1);
        ($this->handler)($command);
    }

    public function test_it_should_throw_exception_for_buffer_percentage_greater_than_100(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $command = DefineGasPriceCommandMother::create(bufferPercentage: 101);
        ($this->handler)($command);
    }

    public function test_it_should_propagate_exception_from_event_bus(): void
    {
        $command = DefineGasPriceCommandMother::create();

        $this->eventStore->expects(self::once())->method('append');
        $this->eventBus
            ->expects(self::once())
            ->method('publish')
            ->willThrowException(new RuntimeException('Event bus failure'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Event bus failure');

        ($this->handler)($command);
    }
}
