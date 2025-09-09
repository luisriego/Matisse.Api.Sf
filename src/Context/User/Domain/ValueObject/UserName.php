<?php

declare(strict_types=1);

namespace App\Context\User\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\ValueObject\StringValueObject;

use function mb_strlen;
use function sprintf;

final readonly class UserName extends StringValueObject
{
    private const MIN_LENGTH = 2;
    private const MAX_LENGTH = 80;

    public function __construct(string $value)
    {
        parent::__construct($value);
        $this->ensureIsValidName($value);
    }

    public static function fromString(string $name): self
    {
        return new self($name);
    }

    private function ensureIsValidName(string $name): void
    {
        $length = mb_strlen($name);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw InvalidArgumentException::createFromMessage(
                sprintf('The name <%s> must be between %d and %d characters long.', $name, self::MIN_LENGTH, self::MAX_LENGTH),
            );
        }
    }
}
