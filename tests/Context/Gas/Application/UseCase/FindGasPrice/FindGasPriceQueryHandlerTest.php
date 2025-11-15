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

    // NUEVO: Test para payload malformado
    public function test_it_should_throw_exception_if_payload_is_malformed(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Payload inválido no evento GasPriceWasDefined');

        $malformedEvent = StoredEvent::create(
            Uuid::random()->value(),
            'gas.price.was.defined',
            ['wrong_key' => 123] // Payload sin 'pricePerM3'
        );

        $repository = $this->createMock(StoredEventRepository::class);
        $repository->method('findByEventType')->willReturn([$malformedEvent]);

        $handler = new FindGasPriceQueryHandler($repository);
        $handler(new FindGasPriceQuery());
    }

    // NUEVO: DataProvider con los casos límite
    public static function priceValuesProvider(): array
    {
        return [
            'positive float' => [5.87, 587],
            'zero value' => [0.0, 0],
            'null value' => [null, 0],
            'non-numeric string' => ['hola', 0],
            'negative float' => [-10.50, -1050],
            'integer value' => [5, 500],
        ];
    }

    // MODIFICADO: Test ahora usa el DataProvider
    #[DataProvider('priceValuesProvider')]
    public function test_it_should_return_correct_price_in_cents_for_various_inputs(mixed $priceInPayload, int $expectedCents): void
    {
        $event = StoredEvent::create(
            Uuid::random()->value(),
            'gas.price.was.defined',
            ['pricePerM3' => $priceInPayload]
        );

        $repository = $this->createMock(StoredEventRepository::class);
        $repository->method('findByEventType')->willReturn([$event]);

        $handler = new FindGasPriceQueryHandler($repository);
        $result = $handler(new FindGasPriceQuery());

        $this->assertSame($expectedCents, $result);
    }
}
