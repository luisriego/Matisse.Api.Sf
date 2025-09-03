<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\EventBus;
use App\Shared\Domain\Event\EventSubscriber;

use function array_key_exists;

final readonly class InMemorySymfonyEventBus implements EventBus
{
    /** @var EventSubscriber[] */
    private iterable $subscribers;

    public function __construct(iterable $subscribers)
    {
        $this->subscribers = $subscribers;
    }

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            foreach ($this->subscribers as $subscriber) {
                $subscribedTo = $subscriber::subscribedTo();
                $eventClass = $event::class;

                if (array_key_exists($eventClass, $subscribedTo)) {
                    $method = $subscribedTo[$eventClass];
                    $subscriber->{$method}($event);
                }
            }
        }
    }
}
