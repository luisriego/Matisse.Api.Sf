<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Domain;

use App\Context\Account\Domain\AccountDescription;
use App\Tests\Shared\Domain\MotherCreator;

final class AccountDescriptionMother
{
    public static function create(?string $value = null): AccountDescription
    {
        return new AccountDescription($value ?? MotherCreator::random()->text(50));
    }
}
