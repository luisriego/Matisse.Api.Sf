<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Application\UseCase\CalculateGasPrice;

use App\Context\Gas\Application\UseCase\CalculateGasPrice\DefineGasPriceCommand;
use App\Context\Gas\Application\UseCase\CalculateGasPrice\DefineGasPriceCommandHandler;
use App\Context\Gas\Domain\Event\GasPriceWasDefined;
use App\Context\Gas\Domain\ValueObject\BufferPercentage;
use App\Context\Gas\Domain\ValueObject\CylinderCapacity;
use App\Context\Gas\Domain\ValueObject\GasAmount;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\Exception\InvalidArgumentException;
use DateMalformedStringException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DefineGasPriceCommandHandlerTest extends TestCase
{
    private EventBus&MockObject $eventBus;
    private DefineGasPriceCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventBus = $this->createMock(EventBus::class);
        $this->handler = new DefineGasPriceCommandHandler($this->eventBus);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function test_it_should_define_gas_price_and_publish_event(): void
    {
        $billAmountInCents = 10000; // 100.00
        $cylinderCapacityInKg = 45;
        $bufferPercentage = 10;

        $command = DefineGasPriceCommandMother::create(
            $billAmountInCents,
            $cylinderCapacityInKg,
            $bufferPercentage
        );

        $expectedPricePerM3 = (
            (new GasAmount($billAmountInCents))->toFloat() /
            (new CylinderCapacity($cylinderCapacityInKg))->toM3()
        ) * (1 + (new BufferPercentage($bufferPercentage))->toFactor());

        $this->eventBus
            ->expects(self::once())
            ->method('publish')
            ->with($this->callback(function (GasPriceWasDefined $event) use ($expectedPricePerM3) {
                $this->assertNotNull($event->aggregateId());
                $this->assertEquals($expectedPricePerM3, $event->pricePerM3);
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

        $expectedPricePerM3 = (
            (new GasAmount(10000))->toFloat() /
            (new CylinderCapacity(45))->toM3()
        ) * (1 + (new BufferPercentage(10))->toFactor());

        $this->eventBus
            ->expects(self::once())
            ->method('publish')
            ->with($this->callback(function (GasPriceWasDefined $event) use ($expectedPricePerM3) {
                $this->assertNotNull($event->aggregateId());
                $this->assertEquals($expectedPricePerM3, $event->pricePerM3);
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

        $expectedPricePerM3 = (
            (new GasAmount($billAmountInCents))->toFloat() /
            (new CylinderCapacity($cylinderCapacityInKg))->toM3()
        ) * (1 + (new BufferPercentage($bufferPercentage))->toFactor());

        $this->eventBus
            ->expects(self::once())
            ->method('publish')
            ->with($this->callback(function (GasPriceWasDefined $event) use ($expectedPricePerM3) {
                $this->assertNotNull($event->aggregateId());
                $this->assertEquals($expectedPricePerM3, $event->pricePerM3);
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

        $expectedPricePerM3 = (
            (new GasAmount($billAmountInCents))->toFloat() /
            (new CylinderCapacity($cylinderCapacityInKg))->toM3()
        ) * (1 + (new BufferPercentage($bufferPercentage))->toFactor());

        $this->eventBus
            ->expects(self::once())
            ->method('publish')
            ->with($this->callback(function (GasPriceWasDefined $event) use ($expectedPricePerM3) {
                $this->assertNotNull($event->aggregateId());
                $this->assertEquals($expectedPricePerM3, $event->pricePerM3);
                return true;
            }));

        ($this->handler)($command);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function test_it_should_throw_exception_for_invalid_bill_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $command = DefineGasPriceCommandMother::create(billAmountInCents: -100);
        ($this->handler)($command);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function test_it_should_throw_exception_for_invalid_cylinder_capacity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $command = DefineGasPriceCommandMother::create(cylinderCapacityInKg: -10);
        ($this->handler)($command);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function test_it_should_throw_exception_for_invalid_buffer_percentage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $command = DefineGasPriceCommandMother::create(bufferPercentage: -1);
        ($this->handler)($command);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function test_it_should_throw_exception_for_buffer_percentage_greater_than_100(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $command = DefineGasPriceCommandMother::create(bufferPercentage: 101);
        ($this->handler)($command);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function test_it_should_propagate_exception_from_event_bus(): void
    {
        $command = DefineGasPriceCommandMother::create();

        $this->eventBus
            ->expects(self::once())
            ->method('publish')
            ->willThrowException(new RuntimeException('Event bus failure'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Event bus failure');

        ($this->handler)($command);
    }
}
