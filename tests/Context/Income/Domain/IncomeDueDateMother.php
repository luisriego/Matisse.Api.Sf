<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Domain;

use App\Context\Income\Domain\ValueObject\IncomeDueDate;
use App\Tests\Shared\Domain\MotherCreator;
use DateTime;

final class IncomeDueDateMother
{
    public static function create(?DateTime $value = null): IncomeDueDate
    {
        if (null !== $value) {
            return new IncomeDueDate($value);
        }

        $faker = MotherCreator::random();
        // Ensure the date is in the future
        return new IncomeDueDate($faker->dateTimeBetween('tomorrow', '+1 year'));
    }
}
