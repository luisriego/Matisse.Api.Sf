<?php

namespace App\Tests\Context\Account\Domain;

use App\Context\Account\Domain\AccountId;
use App\Tests\Shared\Domain\UuidMother;

final class AccountIdMother
{
    public static function create(?string $value = null): AccountId
    {
        return new AccountId($value ?? UuidMother::create());
    }
}