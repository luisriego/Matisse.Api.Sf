<?php

namespace App\Tests\Context\Account\Domain;

use App\Context\Account\Domain\AccountName;
use App\Tests\Shared\Domain\WordMother;

class AccountNameMother
{
    public static function create(?string $value = null): AccountName
    {
        return  new AccountName($value ?? WordMother::create());
    }
}