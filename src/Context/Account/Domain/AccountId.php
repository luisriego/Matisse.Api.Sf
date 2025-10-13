<?php

declare(strict_types=1);

namespace App\Context\Account\Domain;

use App\Shared\Domain\ValueObject\Uuid;

final readonly class AccountId extends Uuid
{
    // Inherits all behavior from Uuid
}
