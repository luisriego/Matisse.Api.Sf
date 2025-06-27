<?php

namespace App\Shared\Domain\Event;

interface DomainEventDispatcherInterface
{
    public function dispatch(DomainEventInterface $event): void;

    public function dispatchAll(array $events): void;
}