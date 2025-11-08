<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Symfony\Normalizer;

use App\Context\Expense\Domain\ExpenseType;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ExpenseTypeNormalizer implements NormalizerInterface
{
    /**
     * @param ExpenseType $object
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        return [
            'id' => $object->id(),
            'code' => $object->code(),
            'name' => $object->name(),
            'distributionMethod' => $object->distributionMethod(),
            'description' => $object->description(),
        ];
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof ExpenseType;
    }
}
