<?php

declare(strict_types=1);

namespace App\Context\Income\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\ValueObject\StringValueObject;

use function mb_strlen;
use function sprintf;
use function trim;

final readonly class IncomeTypeName extends StringValueObject
{
    private const MIN_LENGTH = 5;
    private const MAX_LENGTH = 100;

    public function __construct(string $value)
    {
        parent::__construct($value);
        $this->validate();
    }

    private function validate(): void
    {
        if (trim($this->value) === '') {
            throw new InvalidArgumentException('Income type name cannot be empty.');
        }

        if (mb_strlen($this->value) < self::MIN_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Income type name must be at least %d characters long.', self::MIN_LENGTH),
            );
        }

        if (mb_strlen($this->value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Income type name must be %d characters or less.', self::MAX_LENGTH),
            );
        }
    }
}
