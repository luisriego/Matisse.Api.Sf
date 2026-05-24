<?php

namespace App\Tests\Context\Account\Domain;

use App\Context\Account\Domain\Account;
use App\Context\Account\Domain\AccountId;
use App\Context\Account\Domain\AccountName;

final class AccountMother
{
    public static function create(
        ?AccountId $id = null,
        ?AccountName $name = null
    ): Account {
        return Account::create(
            $id ?? AccountIdMother::create(),
            $name ?? AccountNameMother::create()
        );
    }
}