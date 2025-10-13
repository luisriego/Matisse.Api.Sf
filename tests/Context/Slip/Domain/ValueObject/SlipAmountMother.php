<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Domain\ValueObject;

use App\Context\Slip\Domain\ValueObject\SlipAmount;

final class SlipAmountMother
{
    public static function create(?int $value = null): SlipAmount
    {
        return new SlipAmount($value ?? 10000);
    }
}
