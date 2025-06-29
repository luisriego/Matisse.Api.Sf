<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain;

use App\Shared\Domain\ValueObject\DateTimeValueObject;

class ExpenseDueDate extends DateTimeValueObject {
    public static function fromDateTime(\DateTime $dueDate)
    {
    }
}
