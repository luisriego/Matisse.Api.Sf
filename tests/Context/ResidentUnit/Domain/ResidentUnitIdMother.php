<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Domain;

use App\Context\ResidentUnit\Domain\ResidentUnitId;
use App\Shared\Domain\Uuid;

final class ResidentUnitIdMother
{
    public static function create(?string $value = null): ResidentUnitId
    {
        return new ResidentUnitId($value ?? Uuid::random()->value());
    }
}