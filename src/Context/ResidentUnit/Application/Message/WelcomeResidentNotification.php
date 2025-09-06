<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\Message;

use App\Shared\Application\AsyncMessage;

final readonly class WelcomeResidentNotification implements AsyncMessage
{
    public function __construct(
        public string $name,
        public string $email,
        public string $unitName,
    ) {}
}
