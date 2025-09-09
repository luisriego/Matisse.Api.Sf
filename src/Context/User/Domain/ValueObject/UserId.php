<?php

declare(strict_types=1);

namespace App\Context\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;

final readonly class UserId extends Uuid
{
    public static function fromString(string $id): self
    {
        return new self($id);
    }
}
