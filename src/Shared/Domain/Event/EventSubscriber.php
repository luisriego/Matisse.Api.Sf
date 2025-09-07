<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

interface EventSubscriber
{
    public function __invoke(DomainEvent $event): void;

    public static function subscribedTo(): array;
}
