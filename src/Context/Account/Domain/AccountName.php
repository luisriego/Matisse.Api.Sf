<?php

namespace App\Context\Account\Domain;

use App\Shared\Domain\StringValueObject;

final class AccountName extends StringValueObject {
    public function __construct(string $value)
    {
        $this->ensureIsValidName($value);
        parent::__construct($value);
    }

    private function ensureIsValidName(string $value): void
    {
        $length = strlen($value);

        if ($length < 4) {
            throw new \InvalidArgumentException(
                sprintf('The account name must be at least %d characters', 4)
            );
        }

        if ($length > 100) {
            throw new \InvalidArgumentException(
                sprintf('The account name must not exceed %d characters', 100)
            );
        }
    }
}