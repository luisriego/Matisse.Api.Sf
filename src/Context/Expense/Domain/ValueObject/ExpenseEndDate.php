<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain\ValueObject;

use App\Shared\Domain\ValueObject\DateTimeValueObject;
use DateMalformedStringException;
use DateTime;

class ExpenseEndDate extends DateTimeValueObject
{
    /**
     * @throws DateMalformedStringException
     */
    public static function from(?string $date = null): self
    {
        if ($date === null || trim($date) === '') {
            $year = (int) (new DateTime())->format('Y');
            $dateTime = new DateTime(sprintf('%04d-12-31 12:00:00', $year));
        } else {
            try {
                $dateTime = new DateTime($date);
            } catch (\Exception $e) {
                throw new DateMalformedStringException(
                    sprintf('Data malformada para ExpenseStartDate: "%s"', $date),
                    0,
                    $e
                );
            }
        }

        return new self($dateTime);

    }
}