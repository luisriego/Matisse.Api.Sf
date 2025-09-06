<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\AppendRecipients;

final readonly class AppendRecipientsCommand
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
    ) {}
}
