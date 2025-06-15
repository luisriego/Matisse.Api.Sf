<?php

namespace App\Context\Account\Domain\Exception;

use App\Shared\Domain\ResourceNotFoundException;

class AccountNotFoundException extends ResourceNotFoundException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Account with id "%s" not found', $id));
    }
}