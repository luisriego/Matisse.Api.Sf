<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Application\UseCase\FindGasReading;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Gas\Application\UseCase\FindGasReading\FindGasReadingQuery;
use App\Context\Gas\Application\UseCase\FindGasReading\FindGasReadingQueryHandler;
use App\Context\Gas\Domain\Exception\GasReadingNotFoundException;
use App\Shared\Domain\ValueObject\Uuid;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class FindGasReadingQueryHandlerTest extends TestCase
{
    public function test_it_should_throw_exception_when_no_reading_is_found(): void
    {
        $this->expectException(GasReadingNotFoundException::class);

        $repository = $this->createMock(StoredEventRepository::class);
        $repository->method('findByEventType')->willReturn([]);

        $handler = new FindGasReadingQueryHandler($repository);
        // CORREGIDO: Añadir año y mes
        $handler(new FindGasReadingQuery(Uuid::random()->value(), 2025, 10));
    }

    public function test_it_should_return_the_last_reading_for_the_correct_period(): void
    {
        $targetUnitId = Uuid::random()->value();
        $targetYear = 2025;
        $targetMonth = 10;
        
        $correctReading = 155.5;
        
        $allEvents = [
            StoredEvent::create(
                Uuid::random()->value(),
                'gas.reading.was.recorded',
                ['residentUnitId' => $targetUnitId, 'year' => $targetYear, 'month' => $targetMonth, 'reading' => 150.0],
                new DateTimeImmutable('-2 days')
            ),
            StoredEvent::create(
                Uuid::random()->value(),
                'gas.reading.was.recorded',
                ['residentUnitId' => $targetUnitId, 'year' => $targetYear, 'month' => 9, 'reading' => 140.0]
            ),
            StoredEvent::create(
                Uuid::random()->value(),
                'gas.reading.was.recorded',
                ['residentUnitId' => $targetUnitId, 'year' => $targetYear, 'month' => $targetMonth, 'reading' => $correctReading],
                new DateTimeImmutable('-1 day')
            ),
        ];

        $repository = $this->createMock(StoredEventRepository::class);
        $repository->method('findByEventType')->with('gas.reading.was.recorded')->willReturn($allEvents);

        $handler = new FindGasReadingQueryHandler($repository);
        // CORREGIDO: Añadir año y mes
        $result = $handler(new FindGasReadingQuery($targetUnitId, $targetYear, $targetMonth));

        $this->assertSame($correctReading, $result);
    }
}
