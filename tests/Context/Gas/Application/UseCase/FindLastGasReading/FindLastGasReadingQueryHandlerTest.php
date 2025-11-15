<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Application\UseCase\FindLastGasReading;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Gas\Application\UseCase\FindLastGasReading\FindLastGasReadingQuery;
use App\Context\Gas\Application\UseCase\FindLastGasReading\FindLastGasReadingQueryHandler;
use App\Context\Gas\Domain\Exception\GasReadingNotFoundException;
use App\Shared\Domain\ValueObject\Uuid;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class FindLastGasReadingQueryHandlerTest extends TestCase
{
    public function test_it_should_throw_exception_when_no_reading_is_found(): void
    {
        $this->expectException(GasReadingNotFoundException::class);

        $repository = $this->createMock(StoredEventRepository::class);
        $repository->method('findByEventType')->willReturn([]);

        $handler = new FindLastGasReadingQueryHandler($repository);
        $handler(new FindLastGasReadingQuery(Uuid::random()->value()));
    }

    public function test_it_should_return_the_last_reading_for_the_correct_unit_and_period(): void
    {
        $targetUnitId = Uuid::random()->value();
        $otherUnitId = Uuid::random()->value();
        
        $correctReading = 150.5;
        $olderCorrectReading = 140.0;
        $futureReading = 160.0;
        $otherUnitReading = 999.9;

        $allEvents = [
            StoredEvent::create(
                Uuid::random()->value(),
                'gas.reading.was.recorded',
                ['residentUnitId' => $targetUnitId, 'year' => (int)(new DateTimeImmutable('-4 month'))->format('Y'), 'month' => (int)(new DateTimeImmutable('-4 month'))->format('n'), 'reading' => $olderCorrectReading]
            ),
            StoredEvent::create(
                Uuid::random()->value(),
                'gas.reading.was.recorded',
                ['residentUnitId' => $otherUnitId, 'year' => (int)(new DateTimeImmutable('-3 month'))->format('Y'), 'month' => (int)(new DateTimeImmutable('-3 month'))->format('n'), 'reading' => $otherUnitReading]
            ),
            StoredEvent::create(
                Uuid::random()->value(),
                'gas.reading.was.recorded',
                ['residentUnitId' => $targetUnitId, 'year' => (int)(new DateTimeImmutable('-3 month'))->format('Y'), 'month' => (int)(new DateTimeImmutable('-3 month'))->format('n'), 'reading' => $correctReading]
            ),
            StoredEvent::create(
                Uuid::random()->value(),
                'gas.reading.was.recorded',
                ['residentUnitId' => $targetUnitId, 'year' => (int)(new DateTimeImmutable('-1 month'))->format('Y'), 'month' => (int)(new DateTimeImmutable('-1 month'))->format('n'), 'reading' => $futureReading]
            ),
        ];

        $repository = $this->createMock(StoredEventRepository::class);
        $repository->method('findByEventType')->with('gas.reading.was.recorded')->willReturn($allEvents);

        $handler = new FindLastGasReadingQueryHandler($repository);
        $result = $handler(new FindLastGasReadingQuery($targetUnitId));

        $this->assertSame($correctReading, $result);
    }
}
