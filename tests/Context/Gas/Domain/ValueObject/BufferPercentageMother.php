<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Domain\ValueObject;

use App\Context\Gas\Domain\ValueObject\BufferPercentage;
use Random\RandomException;

final class BufferPercentageMother
{
    public static function create(?int $value = null): BufferPercentage
    {
        // Default to a common buffer value for tests
        return new BufferPercentage($value ?? 10);
    }

    /**
     * @throws RandomException
     */
    public static function random(): BufferPercentage
    {
        // Generate a random percentage between 0 and 20
        return self::create(random_int(0, 20));
    }
}