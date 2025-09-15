<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\ResidentUnit;

final readonly class LinkResidentUnitToUserCommand
{
    public function __construct(
        private string $userId,
        private string $residentUnitId,
    ) {}

    public function userId(): string
    {
        return $this->userId;
    }

    public function residentUnitId(): string
    {
        return $this->residentUnitId;
    }
}