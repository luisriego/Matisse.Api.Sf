<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

interface EventSubscriber
{
    /**
     * Handle the domain event.
     *
     * Implementations should narrow the type in their own docblock:
     *
     *   @param ConcreteEvent $event
     */
    public function __invoke(DomainEvent $event): void;

    /**
     * Returns a map of event class => method name.
     *
     * Example: [SlipWasSubmitted::class => '__invoke']
     *
     * @return array<class-string<DomainEvent>, string>
     */
    public static function subscribedTo(): array;
}
