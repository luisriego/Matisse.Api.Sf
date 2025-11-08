<?php

declare(strict_types=1);

namespace App\Context\Account\Infrastructure\Symfony\Normalizer;

use App\Context\Account\Domain\Account;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AccountNormalizer implements NormalizerInterface
{
    /**
     * @param Account $object
     */
    public function normalize($object, ?string $format = null, array $context = []): array
    {
        return [
            'id' => $object->id(),
            'code' => $object->code(),
            'name' => $object->name(),
            'description' => $object->description(),
            'isActive' => $object->isActive(),
        ];
    }

    public function supportsNormalization($data, ?string $format = null): bool
    {
        return $data instanceof Account;
    }
}
