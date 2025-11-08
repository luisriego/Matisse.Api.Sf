<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Symfony\Normalizer;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ResidentUnitNormalizer implements NormalizerInterface
{
    /**
     * @param ResidentUnit $object
     */
    public function normalize($object, ?string $format = null, array $context = []): array
    {
        return [
            'id' => $object->id(),
            'unit' => $object->unit(),
            'idealFraction' => $object->idealFraction(),
            'isActive' => $object->isActive(),
            'createdAt' => $object->createdAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $object->updatedAt() ? $object->updatedAt()->format('Y-m-d H:i:s') : null,
            'notificationRecipients' => $object->notificationRecipients(),
        ];
    }

    public function supportsNormalization($data, ?string $format = null): bool
    {
        return $data instanceof ResidentUnit;
    }
}
