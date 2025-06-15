<?php

namespace App\Context\Account\Application\UseCase\EnableAccount;

use App\Shared\Application\Command;

final readonly class EnableAccountCommand implements command
{
    public function __construct(private string $id) {}

    public function id(): string
    {
        return  $this->id;
    }
}