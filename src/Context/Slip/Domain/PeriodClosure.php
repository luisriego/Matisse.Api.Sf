<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain;

use App\Context\Slip\Domain\Event\PeriodWasClosed;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;

class PeriodClosure extends AggregateRoot
{
    private readonly DateTimeImmutable $closedAt;

    private function __construct(
        private readonly string $id,
        private readonly int $year,
        private readonly int $month,
    ) {
        $this->closedAt = new DateTimeImmutable();
    }

    public static function close(string $id, int $year, int $month): self
    {
        $closure = new self($id, $year, $month);
        $closure->record(new PeriodWasClosed($id, $year, $month));

        return $closure;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function month(): int
    {
        return $this->month;
    }

    public function closedAt(): DateTimeImmutable
    {
        return $this->closedAt;
    }
}
