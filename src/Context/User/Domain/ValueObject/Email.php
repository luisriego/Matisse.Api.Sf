<?php

declare(strict_types=1);

namespace App\Context\User\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\ValueObject\StringValueObject;

use function filter_var;
use function sprintf;

use const FILTER_VALIDATE_EMAIL;

final readonly class Email extends StringValueObject
{
    public function __construct(string $value)
    {
        parent::__construct($value);
        $this->ensureIsValidEmail($value);
    }

    public static function fromString(string $email): self
    {
        return new self($email);
    }

    private function ensureIsValidEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw InvalidArgumentException::createFromMessage(sprintf('The email <%s> is not valid.', $email));
        }
    }
}
