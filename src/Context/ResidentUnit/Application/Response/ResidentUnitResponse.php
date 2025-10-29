<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\Response;

final class ResidentUnitResponse
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $unit,
        public readonly float $idealFraction,
        public readonly bool $isActive,
        public readonly string $createdAt,
        public readonly ?string $updatedAt,
        public readonly array $notificationRecipients
    ) {
    }
}
