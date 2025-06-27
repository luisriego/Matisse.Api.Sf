<?php

declare(strict_types=1);

namespace App\Context\Account\Domain\Exception;

use App\Shared\Domain\Exception\ResourceNotFoundException;
use function sprintf;

class AccountNotFoundException extends ResourceNotFoundException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Account with id "%s" not found', $id));
    }
}
