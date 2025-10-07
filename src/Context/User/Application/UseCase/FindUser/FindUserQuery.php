<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\FindUser;

use App\Shared\Application\Query;

final readonly class FindUserQuery implements Query
{
    public function __construct(private string $id) {}

    public function id(): string
    {
        return $this->id;
    }
}
