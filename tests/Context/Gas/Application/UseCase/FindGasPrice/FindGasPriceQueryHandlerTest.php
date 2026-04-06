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
    public function test_it_should_throw_exception_when_no_price_is_defined(): void
    {
        $this->expectException(GasPriceNotFoundException::class);

        $repository = $this->createMock(StoredEventRepository::class);
        $repository->method('findByEventType')->willReturn([]);

        $handler = new FindGasPriceQueryHandler($repository);
        $handler(new FindGasPriceQuery());
    }

    public function test_it_should_throw_exception_if_payload_is_malformed(): void
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

    public function test_it_should_return_price_when_payload_uses_cents_key(): void
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
    public function test_it_should_convert_legacy_price_per_m3_reals_to_cents(mixed $pricePerM3Reals, int $expectedCents): void
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

    public function test_cents_key_takes_precedence_over_legacy_key(): void
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
