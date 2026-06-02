<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Application\UseCase\FindGasPrice;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Gas\Application\UseCase\FindGasPrice\FindGasPriceQuery;
use App\Context\Gas\Application\UseCase\FindGasPrice\FindGasPriceQueryHandler;
use App\Context\Gas\Domain\Exception\GasPriceNotFoundException;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FindGasPriceQueryHandlerTest extends TestCase
{
    public function testItShouldThrowExceptionWhenNoPriceIsDefined(): void
    {
        $this->expectException(GasPriceNotFoundException::class);

        $repository = $this->createMock(StoredEventRepository::class);
        $repository->method('findByEventType')->willReturn([]);

        $handler = new FindGasPriceQueryHandler($repository);
        $handler(new FindGasPriceQuery());
    }

    public function testItShouldThrowExceptionIfPayloadIsMalformed(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GasPriceWasDefined payload must contain pricePerM3InCents or legacy pricePerM3.');

        $malformedEvent = StoredEvent::create(
            Uuid::random()->value(),
            'gas.price.was.defined',
            ['wrong_key' => 123],
        );

        $repository = $this->createMock(StoredEventRepository::class);
        $repository->method('findByEventType')->willReturn([$malformedEvent]);

        $handler = new FindGasPriceQueryHandler($repository);
        $handler(new FindGasPriceQuery());
    }

    public function testItShouldReturnPriceWhenPayloadUsesCentsKey(): void
    {
        $event = StoredEvent::create(
            Uuid::random()->value(),
            'gas.price.was.defined',
            ['pricePerM3InCents' => 2600],
        );

        $repository = $this->createMock(StoredEventRepository::class);
        $repository->method('findByEventType')->willReturn([$event]);

        $handler = new FindGasPriceQueryHandler($repository);
        $this->assertSame(2600, $handler(new FindGasPriceQuery()));
    }

    /**
     * @return iterable<string, array{0: mixed, 1: int}>
     */
    public static function legacyPricePerM3RealsProvider(): iterable
    {
        yield 'positive float Reals per m³' => [5.87, 587];
        yield 'zero Reals' => [0.0, 0];
        yield 'integer as Reals' => [5, 500];
        yield 'negative Reals' => [-10.50, -1050];
    }

    #[DataProvider('legacyPricePerM3RealsProvider')]
    public function testItShouldConvertLegacyPricePerM3RealsToCents(mixed $pricePerM3Reals, int $expectedCents): void
    {
        $event = StoredEvent::create(
            Uuid::random()->value(),
            'gas.price.was.defined',
            ['pricePerM3' => $pricePerM3Reals],
        );

        $repository = $this->createMock(StoredEventRepository::class);
        $repository->method('findByEventType')->willReturn([$event]);

        $handler = new FindGasPriceQueryHandler($repository);
        $this->assertSame($expectedCents, $handler(new FindGasPriceQuery()));
    }

    public function testCentsKeyTakesPrecedenceOverLegacyKey(): void
    {
        $event = StoredEvent::create(
            Uuid::random()->value(),
            'gas.price.was.defined',
            [
                'pricePerM3InCents' => 2600,
                'pricePerM3' => 1.0,
            ],
        );

        $repository = $this->createMock(StoredEventRepository::class);
        $repository->method('findByEventType')->willReturn([$event]);

        $handler = new FindGasPriceQueryHandler($repository);
        $this->assertSame(2600, $handler(new FindGasPriceQuery()));
    }
}
