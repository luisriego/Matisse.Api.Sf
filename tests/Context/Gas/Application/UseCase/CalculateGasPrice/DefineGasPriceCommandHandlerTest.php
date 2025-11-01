<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Application\UseCase\CalculateGasPrice;

use App\Context\Gas\Application\UseCase\CalculateGasPrice\DefineGasPriceCommand;
use App\Context\Gas\Application\UseCase\CalculateGasPrice\DefineGasPriceCommandHandler;
use App\Context\Gas\Domain\Bus\GasPriceWasDefined;
use App\Shared\Domain\Event\EventBus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DefineGasPriceCommandHandlerTest extends TestCase
{
    private DefineGasPriceCommandHandler $handler;
    private EventBus|MockObject $eventBus;

    protected function setUp(): void
    {
        $this->eventBus = $this->createMock(EventBus::class);
        $this->handler = new DefineGasPriceCommandHandler($this->eventBus);
    }

    public function test_it_should_create_vos_and_publish_event_with_defaults(): void
    {
        $command = new DefineGasPriceCommand(
            billAmountInCents: 54000,
            cylinderCapacityInKg: null,
            bufferPercentage: null
        );

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (GasPriceWasDefined $event) {
                // --- CORRECTED LINE ---
                // We access the public property directly, not a method.
                $this->assertEquals(26.4, $event->pricePerM3);
                return true;
            }));

        ($this->handler)($command);
    }

    public function test_it_should_use_provided_values_for_calculation(): void
    {
        $command = new DefineGasPriceCommand(
            billAmountInCents: 10000,
            cylinderCapacityInKg: 13,
            bufferPercentage: 5
        );

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (GasPriceWasDefined $event) {
                // --- CORRECTED LINE ---
                // We access the public property directly, not a method.
                $this->assertEqualsWithDelta(16.15, $event->pricePerM3, 0.01);
                return true;
            }));

        ($this->handler)($command);
    }
}