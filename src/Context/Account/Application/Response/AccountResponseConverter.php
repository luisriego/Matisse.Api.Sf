<?php

declare(strict_types=1);

namespace App\Context\Account\Application\Response;

use App\Context\Account\Domain\Account;

class AccountResponseConverter
{
    public function __invoke(Account $account): AccountResponse
    {
        return new AccountResponse(
            $account->id(),
            $account->code(),
            $account->name(),
            $account->description(),
            $account->isActive()
        );
    }
}
