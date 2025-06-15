<?php

namespace App\Context\Account\Application\UpdateAccount;

use App\Shared\Application\Command;

final readonly class UpdateAccountCommand implements Command
{
    public function __construct(private string $id, private string $code, private string $name) {}

    public function id(): string
    {
        return $this->id;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }
}