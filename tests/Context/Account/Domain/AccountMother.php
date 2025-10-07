<?php

namespace App\Tests\Context\Account\Domain;

use App\Context\Account\Domain\Account;
use App\Context\Account\Domain\AccountCode;
use App\Context\Account\Domain\AccountDescription;
use App\Context\Account\Domain\AccountId;
use App\Context\Account\Domain\AccountName;

final class AccountMother
{
    public static function create(
        ?AccountId $id = null,
        ?AccountCode $code = null,
        ?AccountName $name = null,
        ?AccountDescription $description = null
    ): Account {
        return Account::create(
            $id ?? AccountIdMother::create(),
            $code ?? AccountCodeMother::create(),
            $name ?? AccountNameMother::create(),
            $description
        );
    }
}
