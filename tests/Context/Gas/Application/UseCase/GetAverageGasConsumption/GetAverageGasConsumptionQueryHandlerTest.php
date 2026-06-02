<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Application\UseCase\GetAverageGasConsumption;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Gas\Application\UseCase\GetAverageGasConsumption\GetAverageGasConsumptionQuery;
use App\Context\Gas\Application\UseCase\GetAverageGasConsumption\GetAverageGasConsumptionQueryHandler;
use App\Context\Gas\Domain\Exception\NotEnoughReadingsException;
use App\Tests\Shared\Domain\UuidMother;
use DateMalformedStringException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function json_decode;
use function json_encode;

final class GetAverageGasConsumptionQueryHandlerTest extends TestCase
{
    private GetAverageGasConsumptionQueryHandler $handler;
    private MockObject|StoredEventRepository $mockRepository;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRepository = $this->createMock(StoredEventRepository::class);
        $this->handler = new GetAverageGasConsumptionQueryHandler($this->mockRepository);
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function testInvokeWithSufficientReadings(): void
    {
        $residentUnitId = UuidMother::create();
        $query = new GetAverageGasConsumptionQuery($residentUnitId);

        $events = [
            $this->createEvent($residentUnitId, 2023, 1, 100.0),
            $this->createEvent($residentUnitId, 2023, 2, 125.5), // Consumption: 25.5
            $this->createEvent(UuidMother::create(), 2023, 1, 50.0), // Different unit
            $this->createEvent($residentUnitId, 2023, 3, 165.5), // Consumption: 40.0
        ];

        $this->mockRepository->expects($this->once())
            ->method('findByEventType')
            ->with('gas.reading.was.recorded')
            ->willReturn($events);

        // Expected average: (25.5 + 40.0) / 2 = 32.75
        $expectedAverage = 32.75;

        $result = ($this->handler)($query);
        $this->assertEquals($expectedAverage, $result);
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function testInvokeWithNoReadingsForUnit(): void
    {
        $this->expectException(NotEnoughReadingsException::class);

        $residentUnitId = UuidMother::create();
        $query = new GetAverageGasConsumptionQuery($residentUnitId);

        $events = [
            $this->createEvent(UuidMother::create(), 2023, 1, 100.0),
        ];

        $this->mockRepository->expects($this->once())
            ->method('findByEventType')
            ->willReturn($events);

        ($this->handler)($query);
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function testInvokeWithOnlyOneReadingForUnit(): void
    {
        $this->expectException(NotEnoughReadingsException::class);

        $residentUnitId = UuidMother::create();
        $query = new GetAverageGasConsumptionQuery($residentUnitId);

        $events = [
            $this->createEvent($residentUnitId, 2023, 1, 100.0),
        ];

        $this->mockRepository->expects($this->once())
            ->method('findByEventType')
            ->willReturn($events);

        ($this->handler)($query);
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function testInvokeWithNegativeConsumption(): void
    {
        $residentUnitId = UuidMother::create();
        $query = new GetAverageGasConsumptionQuery($residentUnitId);

        $events = [
            $this->createEvent($residentUnitId, 2023, 1, 100.0),
            $this->createEvent($residentUnitId, 2023, 2, 130.0), // Consumption: 30
            $this->createEvent($residentUnitId, 2023, 3, 120.0), // Negative consumption (ignored)
            $this->createEvent($residentUnitId, 2023, 4, 160.0), // Consumption from previous valid: 160 - 120 = 40
        ];

        $this->mockRepository->expects($this->once())
            ->method('findByEventType')
            ->willReturn($events);

        // Expected average: (30 + 40) / 2 = 35
        $expectedAverage = 35.0;

        $result = ($this->handler)($query);
        $this->assertEquals($expectedAverage, $result);
    }

    public function testInvokeWithUnorderedEvents(): void
    {
        $residentUnitId = UuidMother::create();
        $query = new GetAverageGasConsumptionQuery($residentUnitId);

        $events = [
            $this->createEvent($residentUnitId, 2023, 3, 150.0), // Mar
            $this->createEvent($residentUnitId, 2023, 1, 100.0), // Jan
            $this->createEvent($residentUnitId, 2023, 2, 120.0), // Feb
        ];

        $this->mockRepository->expects($this->once())
            ->method('findByEventType')
            ->willReturn($events);

        // Expected consumptions: (120-100)=20, (150-120)=30. Average: (20+30)/2 = 25
        $expectedAverage = 25.0;

        $result = ($this->handler)($query);
        $this->assertEquals($expectedAverage, $result);
    }

    public function testInvokeWithZeroConsumptionPeriod(): void
    {
        $residentUnitId = UuidMother::create();
        $query = new GetAverageGasConsumptionQuery($residentUnitId);

        $events = [
            $this->createEvent($residentUnitId, 2023, 1, 100.0),
            $this->createEvent($residentUnitId, 2023, 2, 120.0), // Consumption: 20
            $this->createEvent($residentUnitId, 2023, 3, 120.0), // Consumption: 0
            $this->createEvent($residentUnitId, 2023, 4, 140.0), // Consumption: 20
        ];

        $this->mockRepository->expects($this->once())
            ->method('findByEventType')
            ->willReturn($events);

        // Expected average: (20 + 0 + 20) / 3 = 13.333...
        $expectedAverage = 13.333;

        $result = ($this->handler)($query);
        $this->assertEquals($expectedAverage, $result);
    }

    public function testInvokeWhenAllConsumptionsAreNegative(): void
    {
        $this->expectException(NotEnoughReadingsException::class);
        $this->expectExceptionMessage('No valid consumption periods found.');

        $residentUnitId = UuidMother::create();
        $query = new GetAverageGasConsumptionQuery($residentUnitId);

        $events = [
            $this->createEvent($residentUnitId, 2023, 1, 150.0),
            $this->createEvent($residentUnitId, 2023, 2, 140.0),
            $this->createEvent($residentUnitId, 2023, 3, 130.0),
        ];

        $this->mockRepository->expects($this->once())
            ->method('findByEventType')
            ->willReturn($events);

        ($this->handler)($query);
    }

    public function testInvokeHandlesRoundingCorrectly(): void
    {
        $residentUnitId = UuidMother::create();
        $query = new GetAverageGasConsumptionQuery($residentUnitId);

        $events = [
            $this->createEvent($residentUnitId, 2023, 1, 100.0),
            $this->createEvent($residentUnitId, 2023, 2, 110.0), // Consumption: 10
            $this->createEvent($residentUnitId, 2023, 3, 121.0), // Consumption: 11
        ];

        $this->mockRepository->expects($this->once())
            ->method('findByEventType')
            ->willReturn($events);

        // Expected average: (10 + 11) / 2 = 10.5
        $expectedAverage = 10.5;

        $result = ($this->handler)($query);
        $this->assertEquals($expectedAverage, $result);
    }

    /**
     * @throws Exception
     */
    private function createEvent(string $residentUnitId, int $year, int $month, float $reading): StoredEvent
    {
        $payload = json_encode([
            'residentUnitId' => $residentUnitId,
            'year' => $year,
            'month' => $month,
            'reading' => $reading,
        ]);

        $event = $this->createMock(StoredEvent::class);
        $event->method('payload')->willReturn(json_decode($payload, true));

        return $event;
    }
}
