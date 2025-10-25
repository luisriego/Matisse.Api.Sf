<?php

declare(strict_types=1);

namespace App\Context\Account\Application;

use App\Context\Account\Domain\Account;

final class AccountTransformer
{
    public function transform(Account $account): AccountResponse
    {
        return new AccountResponse(
            $account->id(),
            $account->code(),
            $account->name(),
            $account->description(),
            $account->isActive(),
        );
    }
}
