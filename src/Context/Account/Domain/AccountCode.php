<?php

namespace App\Context\Account\Domain;

use App\Shared\Domain\StringValueObject;

final class AccountCode extends StringValueObject
{
    public function __construct(string $value)
    {
        $upperValue = strtoupper($value);
        $this->ensureIsValidCode($upperValue);
        parent::__construct($upperValue);
    }

    private function ensureIsValidCode(string $value): void
    {
        // Rule 1: Maximum 10 characters
        if (strlen($value) > 10) {
            throw new \InvalidArgumentException(
                sprintf('The account code <%s> must not exceed 10 characters', $value)
            );
        }

        // Rule 2: First 3 characters must be letters
        if (!preg_match('/^[A-Z]{3}/', $value)) {
            throw new \InvalidArgumentException(
                sprintf('The account code <%s> must start with 3 letters', $value)
            );
        }

        // Rule 3: Next 2 characters must be numbers
        if (!preg_match('/^[A-Z]{3}[0-9]{2}/', $value)) {
            throw new \InvalidArgumentException(
                sprintf('The account code <%s> must have 2 numbers after the first 3 letters', $value)
            );
        }

        // Rule 4: Remaining characters must be letters or numbers
        if (!preg_match('/^[A-Z]{3}[0-9]{2}[A-Z0-9]*$/', $value)) {
            throw new \InvalidArgumentException(
                sprintf('The account code <%s> can only contain letters and numbers after the first 5 characters', $value)
            );
        }
    }
}