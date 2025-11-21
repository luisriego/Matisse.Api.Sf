<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Symfony\Normalizer;

use App\Context\User\Domain\User;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

class UserNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    /**
     * @param User $object
     */
    public function normalize($object, ?string $format = null, array $context = []): array
    {
        $data = [
            'id' => $object->id(),
            'name' => $object->name(),
            'lastName' => $object->lastName(),
            'gender' => $object->gender(),
            'phoneNumber' => $object->phoneNumber(),
            'email' => $object->getEmail(),
            'roles' => $object->getRoles(),
            'isActive' => $object->isActive(),
            'createdAt' => $object->createdAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $object->updatedAt()?->format('Y-m-d H:i:s'),
        ];

        // Handle residentUnit relation
        if ($object->getResidentUnit() !== null) {
            // Normalize the ResidentUnit object using the serializer
            // This will use ResidentUnitNormalizer if it exists, or default normalizers
            $data['residentUnit'] = $this->serializer->normalize($object->getResidentUnit(), $format, $context);
        } else {
            $data['residentUnit'] = null;
        }

        return $data;
    }

    public function supportsNormalization($data, ?string $format = null): bool
    {
        return $data instanceof User;
    }
}
