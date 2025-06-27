<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use DateTimeImmutable;

interface DomainEventInterface
{
    public function occurredOn(): DateTimeImmutable;
}