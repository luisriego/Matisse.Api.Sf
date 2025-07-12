<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use DateMalformedStringException;
use DateTime;
use InvalidArgumentException;

use function sprintf;

final readonly class DateRange
{
    public function __construct(
        private DateTime $startDate,
        private DateTime $endDate,
    ) {
        if ($this->startDate > $this->endDate) {
            throw new InvalidArgumentException('Start date cannot be after end date');
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function fromMonth(int $year, int $month): self
    {
        $startDate = new DateTime(sprintf('%d-%02d-01 00:00:00', $year, $month));
        $endDate = new DateTime(sprintf('%d-%02d-01 23:59:59', $year, $month));
        $endDate->modify('last day of this month');

        return new self($startDate, $endDate);
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function fromDates(string $startDate, string $endDate): self
    {
        return new self(
            new DateTime($startDate),
            new DateTime($endDate),
        );
    }

    public function startDate(): DateTime
    {
        return $this->startDate;
    }

    public function endDate(): DateTime
    {
        return $this->endDate;
    }

    public function contains(DateTime $date): bool
    {
        return $date >= $this->startDate && $date <= $this->endDate;
    }

    public function toString(): string
    {
        return sprintf(
            '%s to %s',
            $this->startDate->format('Y-m-d H:i:s'),
            $this->endDate->format('Y-m-d H:i:s'),
        );
    }
}
