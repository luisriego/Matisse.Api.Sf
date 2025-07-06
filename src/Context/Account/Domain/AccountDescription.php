<?php

declare(strict_types=1);

namespace App\Context\Account\Domain;

use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\ValueObject\StringValueObject;

use function mb_strlen;
use function sprintf;

readonly class AccountDescription extends StringValueObject
{
    public function __construct(string $description)
    {
        $this->ensureIsValidDescription($description);
        parent::__construct($description);
    }

    private function ensureIsValidDescription(string $value): void
    {
        $length = mb_strlen($value);

        if ($length < 10) {
            throw new InvalidArgumentException(
                sprintf('The account description must be at least %d characters', 10),
            );
        }

        if ($length > 255) {
            throw new InvalidArgumentException(
                sprintf('The account description must not exceed %d characters', 255),
            );
        }
    }
}
