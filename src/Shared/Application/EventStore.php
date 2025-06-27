<?php

declare(strict_types=1);

namespace App\Shared\Application;

use App\Shared\Domain\Event\DomainEvent;

interface EventStore
{
    public function append(DomainEvent $event): void;
}