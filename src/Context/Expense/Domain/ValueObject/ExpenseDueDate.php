<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain\ValueObject;

use App\Shared\Domain\ValueObject\DateTimeValueObject;
use DateTime;

class ExpenseDueDate extends DateTimeValueObject
{
    public static function fromDateTime(DateTime $dueDate): void {}
}
