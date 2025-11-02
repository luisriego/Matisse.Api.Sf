<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Domain\ValueObject;

use App\Context\Gas\Domain\ValueObject\GasId;
use App\Tests\Shared\Domain\UuidMother;

final class GasIdMother
{
    public static function create(?string $value = null): GasId
    {
        return new GasId($value ?? UuidMother::create());
    }
}
