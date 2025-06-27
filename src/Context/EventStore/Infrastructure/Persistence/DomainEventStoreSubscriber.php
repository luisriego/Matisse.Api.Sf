<?php

declare(strict_types=1);

namespace App\Context\EventStore\Infrastructure\Persistence;

use App\Context\Expense\Domain\Bus\ExpenseWasEntered;
use App\Shared\Application\EventStore;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\EventSubscriber;


final readonly class DomainEventStoreSubscriber implements EventSubscriber
{
    public function __construct(private EventStore $eventStore) {}

    public static function subscribedTo(): array
    {
        return [DomainEvent::class];
    }

    public function __invoke(DomainEvent $event): void
    {
        if ($event instanceof ExpenseWasEntered) {
            return; // Ignorar este evento, ya tiene un suscriptor dedicado
        }

        $this->eventStore->append($event);

    }
}