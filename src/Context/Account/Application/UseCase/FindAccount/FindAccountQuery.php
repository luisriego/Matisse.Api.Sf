<?php

namespace App\Context\Account\Application\UseCase\FindAccount;

use App\Shared\Application\Query;

final readonly class FindAccountQuery implements Query
{
    public function __construct(private string $id) {}

    public function id(): string
    {
        return $this->id;
    }
}