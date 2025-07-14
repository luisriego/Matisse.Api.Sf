<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

use InvalidArgumentException;

class DueDateMustBeInTheFutureException extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('O ingresso de ter vencimento hoje ou em uma data futura');
    }
}