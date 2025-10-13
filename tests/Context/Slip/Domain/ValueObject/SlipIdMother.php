<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Domain\ValueObject;

use App\Context\Slip\Domain\ValueObject\SlipId;
use App\Shared\Domain\ValueObject\Uuid;

final class SlipIdMother
{
    public static function create(?string $value = null): SlipId
    {
        return new SlipId($value ?? Uuid::random()->value());
    }
}
