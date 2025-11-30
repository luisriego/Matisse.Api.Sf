<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Symfony\Normalizer;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

class ResidentUnitNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

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
            'updatedAt' => $object->updatedAt()?->format('Y-m-d H:i:s'),
            'notificationRecipients' => $object->notificationRecipients(), // Already an array
        ];
    }

    public function supportsNormalization($data, ?string $format = null): bool
    {
        return $data instanceof ResidentUnit;
    }
}
