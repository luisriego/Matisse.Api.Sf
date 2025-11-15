<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Application\UseCase\FindGasReading;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Gas\Application\UseCase\FindGasReading\FindGasReadingQuery;
use App\Context\Gas\Application\UseCase\FindGasReading\FindGasReadingQueryHandler;
use App\Context\Gas\Domain\Exception\GasReadingNotFoundException;
use App\Context\ResidentUnit\Domain\ResidentUnitId; // Importar
use App\Shared\Domain\ValueObject\Month; // Importar
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Domain\ValueObject\Year; // Importar
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
        $handler(new FindGasReadingQuery(new ResidentUnitId(Uuid::random()->value()), new Year(2025), new Month(10))); // Usar VOs
    }

    public function test_it_should_return_the_last_reading_for_the_correct_period(): void
    {
        $targetUnitId = new ResidentUnitId(Uuid::random()->value()); // Usar VO
        $targetYear = new Year(2025); // Usar VO
        $targetMonth = new Month(10); // Usar VO
        
        $correctReading = 155.5;
        
        $allEvents = [
            StoredEvent::create(
                Uuid::random()->value(),
                'gas.reading.was.recorded',
                ['residentUnitId' => $targetUnitId->value(), 'year' => $targetYear->value(), 'month' => $targetMonth->value(), 'reading' => 150.0],
                new DateTimeImmutable('-2 days')
            ),
            StoredEvent::create(
                Uuid::random()->value(),
                'gas.reading.was.recorded',
                ['residentUnitId' => $targetUnitId->value(), 'year' => $targetYear->value(), 'month' => 9, 'reading' => 140.0]
            ),
            StoredEvent::create(
                Uuid::random()->value(),
                'gas.reading.was.recorded',
                ['residentUnitId' => $targetUnitId->value(), 'year' => $targetYear->value(), 'month' => $targetMonth->value(), 'reading' => $correctReading],
                new DateTimeImmutable('-1 day')
            ),
        ];

        $repository = $this->createMock(StoredEventRepository::class);
        $repository->method('findByEventType')->with('gas.reading.was.recorded')->willReturn($allEvents);

        $handler = new FindGasReadingQueryHandler($repository);
        $result = $handler(new FindGasReadingQuery($targetUnitId, $targetYear, $targetMonth)); // Usar VOs

        $this->assertSame($correctReading, $result);
    }
}
