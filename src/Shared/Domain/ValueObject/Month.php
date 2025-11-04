<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Exception\InvalidArgumentException;

use function sprintf;

final class Month extends IntValueObject
{
    public function __construct(int $value)
    {
        if ($value < 1 || $value > 12) {
            throw new InvalidArgumentException(sprintf('O mês deve ser un valor entre 1 e 12. <%d> não é válido.', $value));
        }

        parent::__construct($value);
    }
}
