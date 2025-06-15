<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\EnableAccount;

use App\Shared\Application\Command;

final readonly class EnableAccountCommand implements Command
{
    public function __construct(private string $id) {}

    public function id(): string
    {
        return $this->id;
    }
}
