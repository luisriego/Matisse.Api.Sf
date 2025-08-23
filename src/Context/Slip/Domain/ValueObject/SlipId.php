<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;

final readonly class SlipId extends Uuid
{
    public static function fromString(string $id): SlipId
    {
        return new SlipId($id);
    }
}
