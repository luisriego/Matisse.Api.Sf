<?php

namespace App\Shared\Domain;

use Symfony\Component\Uid\Uuid as SfUuid;
use Stringable;

use function sprintf;

readonly class Uuid implements Stringable
{
    public function __construct(protected string $value)
    {
        $this->ensureIsValidUuid($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function fromString(string $id): self
    {
        return new static($id);
    }

    public static function random(): self
    {
        return new static(SfUuid::v4()->toRfc4122());
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(Uuid $other): bool
    {
        return $this->value() === $other->value();
    }

    protected function ensureIsValidUuid(string $id): void
    {
        if (!SfUuid::isValid($id)) {
            throw new InvalidArgumentException(sprintf('<%s> does not allow the value <%s>.', static::class, $id));
        }
    }
}