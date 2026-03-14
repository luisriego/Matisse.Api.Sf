<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\Event;

use App\Context\EventStore\Domain\StoredEventRepository;
use App\Context\Gas\Domain\Event\GasPriceWasDefined;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class PersistGasPriceOnEventStore
{
    public function __construct(private StoredEventRepository $repository) {}

    public function __invoke(GasPriceWasDefined $event): void
    {
        $this->repository->append($event);
    }
}
