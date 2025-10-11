<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\FindResidentUnitById;

use App\Shared\Application\Query;

final readonly class FindResidentUnitByIdQuery implements Query
{
    public function __construct(private string $id) {}

    public function id(): string
    {
        return $this->id;
    }
}
