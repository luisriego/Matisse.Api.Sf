<?php

declare(strict_types=1);

namespace App\Context\Account\Application\Transformer;

use App\Context\Account\Domain\Account;

class AccountTransformer
{
    public function transform(Account $account): array
    {
        return [
            'id' => $account->id(),
            'code' => $account->code(),
            'name' => $account->name(),
            'description' => $account->description(),
            'isActive' => $account->isActive(),
        ];
    }
}
