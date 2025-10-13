<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Domain\ValueObject;

use App\Context\Slip\Domain\ValueObject\SlipDueDate;

final class SlipDueDateMother
{
    public static function create(?string $value = null): SlipDueDate
    {
        return SlipDueDate::fromString($value ?? 'now');
    }
}
