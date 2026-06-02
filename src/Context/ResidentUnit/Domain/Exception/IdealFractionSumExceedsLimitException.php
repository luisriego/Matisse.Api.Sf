<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Domain\Exception;

use DomainException;

use function number_format;
use function sprintf;

final class IdealFractionSumExceedsLimitException extends DomainException
{
    public function __construct(string $message = 'A soma das frações ideais não pode ser maior que 1.')
    {
        parent::__construct($message);
    }

    public static function fromTotals(float $currentActiveTotal, float $newFraction): self
    {
        $projectedTotal = $currentActiveTotal + $newFraction;

        return new self(sprintf(
            'A soma das frações ideais não pode ser maior que 1. (activas: %s, nova fração: %s, total projectado: %s).',
            self::formatFraction($currentActiveTotal),
            self::formatFraction($newFraction),
            self::formatFraction($projectedTotal),
        ));
    }

    private static function formatFraction(float $value): string
    {
        return number_format($value, 8, '.', '');
    }
}
