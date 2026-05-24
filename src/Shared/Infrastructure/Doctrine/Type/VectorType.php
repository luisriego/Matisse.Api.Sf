<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

use function array_map;
use function explode;
use function implode;
use function is_array;
use function trim;

/**
 * Doctrine DBAL custom type for pgvector's `vector(N)` column.
 *
 * PHP representation : float[]
 * Database format    : '[0.1,0.2,...]'
 */
final class VectorType extends Type
{
    public const NAME = 'vector';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $dim = $column['dimensions'] ?? 768;

        return "vector({$dim})";
    }

    /**
     * @param float[]|string|null $value
     * @return float[]|null
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return array_map('floatval', $value);
        }

        $clean = trim((string) $value, '[]');

        if ($clean === '') {
            return [];
        }

        return array_map('floatval', explode(',', $clean));
    }

    /**
     * @param float[]|null $value
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        return '[' . implode(',', $value) . ']';
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
