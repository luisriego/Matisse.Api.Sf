<?php

declare(strict_types=1);

namespace App\Context\Expense\Domain;

use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\ValueObject\StringValueObject;

final readonly class ExpenseTypeDistributionMethod extends StringValueObject
{
    public const string EQUAL      = 'EQUAL';
    public const string FRACTION   = 'FRACTION';
    public const string INDIVIDUAL = 'INDIVIDUAL';

    private const array ALLOWED = [
        self::EQUAL,
        self::FRACTION,
        self::INDIVIDUAL,
    ];

    public function __construct(string $value)
    {
        if (!\in_array($value, self::ALLOWED, true)) {
            throw InvalidArgumentException::createFromMessage($value);
        }

        parent::__construct($value);
    }

    public static function equal(): self
    {
        return new self(self::EQUAL);
    }

    public static function fraction(): self
    {
        return new self(self::FRACTION);
    }

    public static function individual(): self
    {
        return new self(self::INDIVIDUAL);
    }
}
