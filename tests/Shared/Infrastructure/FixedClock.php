<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure;

use App\Shared\Domain\Clock;
use DateTimeImmutable;

final class FixedClock implements Clock
{
    private DateTimeImmutable $now;

    /**
     * @throws \DateMalformedStringException
     */
    public function __construct(?string $now = 'now')
    {
        $this->now = new DateTimeImmutable($now);
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    /**
     * @throws \Exception
     */
    public function set(string $dateTime): void
    {
        $this->now = new DateTimeImmutable($dateTime);
    }
}