<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;

use function sprintf;

final class Year extends IntValueObject
{
    public function __construct(int $value)
    {
        if ($value < 2000 || $value > 2100) {
            throw new InvalidArgumentException(sprintf('O ano <%d> não é válido.', $value));
        }

        parent::__construct($value);
    }
}
