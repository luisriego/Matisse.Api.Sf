<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\ResidentUnit;

final readonly class UnlinkResidentUnitFromUserCommand
{
    public function __construct(private string $userId) {}

    public function userId(): string
    {
        return $this->userId;
    }
}