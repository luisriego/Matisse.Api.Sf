<?php

declare(strict_types=1);

namespace App\Context\Account\Application;

final class AccountResponse
{
    public function __construct(
        private readonly string $id,
        private readonly string $code,
        private readonly ?string $name,
        private readonly ?string $description,
        private readonly ?bool $isActive,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }
}
