<?php

declare(strict_types=1);

namespace App\Context\Expense\Application;

use App\Context\Expense\Domain\Bus\ExpenseWasEntered;
use App\Shared\Application\EventStore;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\EventSubscriber;

final readonly class StoreExpenseEnteredEventSubscriber implements EventSubscriber
{
    public function __construct(
        private EventStore $eventStore,
    ) {}

    public function __invoke(DomainEvent $event): void
    {
        $this->eventStore->append($event);
    }

    public static function subscribedTo(): array
    {
        return [
            ExpenseWasEntered::class,
        ];
    }
}
