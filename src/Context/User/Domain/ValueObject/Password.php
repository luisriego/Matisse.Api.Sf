<?php

declare(strict_types=1);

namespace App\Context\User\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\ValueObject\StringValueObject;

use function mb_strlen;
use function sprintf;

final readonly class Password extends StringValueObject
{
    private const MIN_LENGTH = 6;
    private const MAX_LENGTH = 55;

    public function __construct(string $value)
    {
        parent::__construct($value);
        $this->ensureIsValidPassword($value);
    }

    public static function fromString(string $password): self
    {
        return new self($password);
    }

    private function ensureIsValidPassword(string $password): void
    {
        $length = mb_strlen($password);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw InvalidArgumentException::createFromMessage(
                sprintf('The password must be between %d and %d characters long.', self::MIN_LENGTH, self::MAX_LENGTH),
            );
        }
    }
}
