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
        return [
            'id' => $object->id(),
            'email' => $object->getEmail(),
            'name' => $object->name(),
            'lastName' => $object->lastName(),
            'gender' => $object->gender(),
            'phoneNumber' => $object->phoneNumber(),
            'roles' => $object->getRoles(),
            'isActive' => $object->isActive(),
            'residentUnit' => $this->serializer->normalize($object->getResidentUnit()),
        ];
    }

    public function supportsNormalization($data, ?string $format = null): bool
    {
        return $data instanceof User;
    }
}
