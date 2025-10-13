<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Persistence\Doctrine;

use App\Context\EventStore\Domain\StoredEvent;
use App\Context\EventStore\Domain\StoredEventRepository;
use App\Shared\Application\EventStore;
use App\Shared\Domain\Event\DomainEvent;

class DoctrineEventStore implements EventStore
{
    public function __construct(
        private StoredEventRepository $storedEventRepository,
    ) {}

    public function append(DomainEvent $event): void
    {
        $storedEvent = StoredEvent::create(
            $event->aggregateId(),
            $event::eventName(),
            $event->toPrimitives(),
        );

        $this->storedEventRepository->save($storedEvent);
    }
}
