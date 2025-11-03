<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Application\UseCase\RecordGasReading;

use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Gas\Application\UseCase\RecordGasReading\RecordGasReadingCommandHandler;
use App\Context\Gas\Domain\Bus\GasReadingWasRecorded;
use App\Shared\Domain\Event\EventBus;
use DateMalformedStringException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RecordGasReadingCommandHandlerTest extends TestCase
{
    private EventBus&MockObject $eventBus;
    private StoredEventRepository&MockObject $storedEventRepository;
    private RecordGasReadingCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventBus = $this->createMock(EventBus::class);
        $this->storedEventRepository = $this->createMock(StoredEventRepository::class);
        $this->handler = new RecordGasReadingCommandHandler($this->eventBus, $this->storedEventRepository);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function test_it_should_record_gas_reading_and_publish_event(): void
    {
        $command = RecordGasReadingCommandMother::create();

        $this->storedEventRepository->method('findByEventType')->willReturn([]);

        $this->eventBus
            ->expects(self::once())
            ->method('publish')
            ->with($this->callback(function (GasReadingWasRecorded $event) use ($command) {
                $this->assertSame($command->id(), $event->aggregateId());
                $this->assertSame($command->residentUnitId(), $event->residentUnitId);
                $this->assertSame($command->year(), $event->year);
                $this->assertSame($command->month(), $event->month);
                $this->assertSame($command->reading(), $event->reading);
                $this->assertNull($event->price);
                return true;
            }));

        ($this->handler)($command);
    }

    public function test_it_should_throw_exception_for_invalid_reading(): void
    {
        $this->expectException(\App\Shared\Domain\Exception\InvalidArgumentException::class);
        
        $command = RecordGasReadingCommandMother::create(reading: -100.0);

        ($this->handler)($command);
    }

    public function test_it_should_throw_exception_for_invalid_month(): void
    {
        $this->expectException(\App\Shared\Domain\Exception\InvalidArgumentException::class);
        
        $command = RecordGasReadingCommandMother::create(month: 13);

        ($this->handler)($command);
    }
    
    public function test_it_should_throw_exception_for_invalid_year(): void
    {
        $this->expectException(\App\Shared\Domain\Exception\InvalidArgumentException::class);
        
        $command = RecordGasReadingCommandMother::create(year: 1900);

        ($this->handler)($command);
    }

    public function test_it_should_propagate_exception_from_event_bus(): void
    {
        $command = RecordGasReadingCommandMother::create();

        $this->storedEventRepository->method('findByEventType')->willReturn([]);

        $this->eventBus
            ->expects(self::once())
            ->method('publish')
            ->willThrowException(new RuntimeException('Event bus failure'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Event bus failure');

        ($this->handler)($command);
    }
}
