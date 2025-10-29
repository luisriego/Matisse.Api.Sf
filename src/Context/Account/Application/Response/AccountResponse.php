<?php

declare(strict_types=1);

namespace App\Context\Account\Application\Response;

final readonly class AccountResponse
{
    public function __construct(
        public string  $id,
        public string  $code,
        public ?string $name,
        public ?string $description,
        public ?bool   $isActive
    ) {
    }
}
