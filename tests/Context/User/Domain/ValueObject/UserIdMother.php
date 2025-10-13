<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Domain\ValueObject;

use App\Context\User\Domain\ValueObject\UserId;
use Symfony\Component\Uid\Uuid;

final class UserIdMother
{
    public static function create(?string $value = null): UserId
    {
        return UserId::fromString($value ?? Uuid::v4()->toRfc4122());
    }
}
