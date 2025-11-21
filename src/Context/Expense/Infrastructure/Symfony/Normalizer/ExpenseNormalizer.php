<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Symfony\Normalizer;

use App\Context\Expense\Domain\Expense;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

class ExpenseNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    /**
     * @param Expense $object
     */
    public function normalize($object, ?string $format = null, array $context = []): array
    {
        return [
            'id' => $object->id(),
            'amount' => $object->amount(),
            'description' => $object->description(),
            'dueDate' => $object->dueDate()->format('Y-m-d'),
            'paidAt' => $object->paidAt()?->format('Y-m-d'),
            'createdAt' => $object->createdAt()->format('Y-m-d H:i:s'),
            'residentUnitId' => $object->residentUnitId(),
            'type' => $this->serializer->normalize($object->type()),
            'account' => $this->serializer->normalize($object->account()),
            'recurringExpense' => $object->recurringExpense()?->id(),
        ];
    }

    public function supportsNormalization($data, ?string $format = null): bool
    {
        return $data instanceof Expense;
    }
}
