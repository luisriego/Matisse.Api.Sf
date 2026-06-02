<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\FindGasPrice;

use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Gas\Domain\Exception\GasPriceNotFoundException;
use App\Shared\Application\QueryHandler;
use RuntimeException;

use function array_key_exists;
use function end;
use function is_array;
use function is_numeric;
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

        if (!is_array($payload)) {
            throw new RuntimeException('GasPriceWasDefined payload is invalid.');
        }

        if (array_key_exists('pricePerM3InCents', $payload) && is_numeric($payload['pricePerM3InCents'])) {
            return (int) $payload['pricePerM3InCents'];
        }

        // Legacy: pricePerM3 stored as Reals per m³ (float)
        if (array_key_exists('pricePerM3', $payload) && is_numeric($payload['pricePerM3'])) {
            return (int) round((float) $payload['pricePerM3'] * 100);
        }

        throw new RuntimeException('GasPriceWasDefined payload must contain pricePerM3InCents or legacy pricePerM3.');
    }
}
