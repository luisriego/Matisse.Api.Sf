<?php

declare(strict_types=1);

namespace App\Context\Account\Domain\Exception;

use App\Shared\Domain\Exception\ResourceNotFoundException;

class ExpenseNotFoundException extends ResourceNotFoundException
{
    public function __construct(string $id)
    {
        parent::__construct('Expense with id "' . $id . '" not found');
    }
}