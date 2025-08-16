<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure;

use App\Shared\Domain\Clock;
use DateTimeImmutable;

final class FixedClock implements Clock
{
    private DateTimeImmutable $now;

    public function __construct(string $dateTime = 'now')
    {
        $this->now = new DateTimeImmutable($dateTime);
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function set(string $dateTime): void
    {
        $this->now = new DateTimeImmutable($dateTime);
    }
}