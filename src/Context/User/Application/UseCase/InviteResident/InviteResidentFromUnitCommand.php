<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\InviteResident;

use App\Shared\Application\Command;

final readonly class InviteResidentFromUnitCommand implements Command
{
    public function __construct(
        private string $residentUnitId,
        private string $email,
        private ?string $name = null,
    ) {}

    public function residentUnitId(): string
    {
        return $this->residentUnitId;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function name(): ?string
    {
        return $this->name;
    }
}
