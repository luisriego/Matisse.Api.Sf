<?php

declare(strict_types=1);

namespace App\Tests\Shared\Domain;

use App\Shared\Domain\ValueObject\DateRange;
use DateMalformedStringException;

final class DateRangeMother
{
    public static function create(): DateRange
    {
        $date1 = MotherCreator::random()->dateTime();
        $date2 = MotherCreator::random()->dateTime();

        if ($date1 > $date2) {
            return new DateRange($date2, $date1);
        }

        return new DateRange($date1, $date2);
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function fromMonth(int $year, int $month): DateRange
    {
        return DateRange::fromMonth($year, $month);
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function fromDates(string $startDate, string $endDate): DateRange
    {
        return DateRange::fromDates($startDate, $endDate);
    }
}
