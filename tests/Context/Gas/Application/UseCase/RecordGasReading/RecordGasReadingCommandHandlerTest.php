<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Application\UseCase\RecordGasReading;

use App\Context\Gas\Application\UseCase\RecordGasReading\RecordGasReadingCommandHandler;
use App\Context\Gas\Domain\Event\GasReadingWasRecorded;
use App\Shared\Application\EventStore;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\Exception\InvalidArgumentException;
use DateMalformedStringException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RecordGasReadingCommandHandlerTest extends TestCase
{
    private EventBus&MockObject $eventBus;
    private EventStore&MockObject $eventStore;
    private RecordGasReadingCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventBus = $this->createMock(EventBus::class);
        $this->eventStore = $this->createMock(EventStore::class);
        $this->handler = new RecordGasReadingCommandHandler($this->eventBus, $this->eventStore);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testItShouldRecordGasReadingAndPublishEvent(): void
    {
        $command = RecordGasReadingCommandMother::create();

        $this->eventStore->expects(self::once())->method('append');
        $this->eventBus
            ->expects(self::once())
            ->method('publish')
            ->with($this->callback(function (GasReadingWasRecorded $event) use ($command) {
                $this->assertSame($command->id(), $event->aggregateId());
                $this->assertSame($command->residentUnitId(), $event->residentUnitId);
                $this->assertSame($command->year(), $event->year);
                $this->assertSame($command->month(), $event->month);
                $this->assertSame($command->reading(), $event->reading);

                return true;
            }));

        ($this->handler)($command);
    }

    public function testItShouldThrowExceptionForInvalidReading(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $command = RecordGasReadingCommandMother::create(reading: -100.0);

        ($this->handler)($command);
    }

    public function testItShouldThrowExceptionForInvalidMonth(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $command = RecordGasReadingCommandMother::create(month: 13);

        ($this->handler)($command);
    }

    public function testItShouldThrowExceptionForInvalidYear(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $command = RecordGasReadingCommandMother::create(year: 1900);

        ($this->handler)($command);
    }

    public function testItShouldPropagateExceptionFromEventBus(): void
    {
        $command = RecordGasReadingCommandMother::create();

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
