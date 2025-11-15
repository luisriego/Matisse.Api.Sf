<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\FindGasPrice;

use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Gas\Domain\Exception\GasPriceNotFoundException;
use App\Shared\Application\QueryHandler;
use RuntimeException;

use function array_key_exists;
use function end;
use function round;

final readonly class FindGasPriceQueryHandler implements QueryHandler
{
    public function __construct(private StoredEventRepository $storedEventRepository) {}

    public function __invoke(FindGasPriceQuery $query): int
    {
        $events = $this->storedEventRepository->findByEventType('gas.price.was.defined');

        if (empty($events)) {
            throw new GasPriceNotFoundException();
        }

        $latestEvent = end($events);
        $payload = $latestEvent->payload();

        if (!array_key_exists('pricePerM3', $payload)) { // <-- CORREGIDO
            throw new RuntimeException('Payload inválido no evento GasPriceWasDefined. A chave "pricePerM3" está faltando.');
        }

        $priceAsFloat = (float) $payload['pricePerM3'];

        return (int) round($priceAsFloat * 100);
    }
}
