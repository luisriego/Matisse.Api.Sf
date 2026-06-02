<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Domain\ValueObject;

use App\Context\BillingPolicy\Domain\BillingPolicyMonthSnapshot;
use DateTimeImmutable;

use function array_key_exists;

final readonly class BillingPolicySnapshot
{
    public function __construct(
        private string $targetMonth,
        private int $extraFeePerUnitCents,
        private int $reserveFundPerUnitCents,
        private int $syndicShareTotalCents,
        private ?int $gasPricePerM3Cents,
        private DateTimeImmutable $recordedAt,
    ) {}

    public static function fromEntity(BillingPolicyMonthSnapshot $entity): self
    {
        return new self(
            $entity->targetMonth(),
            $entity->extraFeePerUnitCents(),
            $entity->reserveFundPerUnitCents(),
            $entity->syndicShareTotalCents(),
            $entity->gasPricePerM3Cents(),
            $entity->recordedAt(),
        );
    }

    /**
     * @param array<string, mixed> $openingPayload
     */
    public static function fromOpeningReference(
        array $openingPayload,
        DateTimeImmutable $recordedAt,
        ?int $gasPricePerM3Cents,
    ): self {
        return new self(
            (string) $openingPayload['referenceMonth'],
            (int) $openingPayload['extraFeePerUnitCents'],
            (int) $openingPayload['reserveFundPerUnitCents'],
            array_key_exists('expectedSyndicShareTotalCents', $openingPayload)
                && $openingPayload['expectedSyndicShareTotalCents'] !== null
                ? (int) $openingPayload['expectedSyndicShareTotalCents']
                : 60_000,
            $gasPricePerM3Cents,
            $recordedAt,
        );
    }

    public function targetMonth(): string
    {
        return $this->targetMonth;
    }

    public function extraFeePerUnitCents(): int
    {
        return $this->extraFeePerUnitCents;
    }

    public function reserveFundPerUnitCents(): int
    {
        return $this->reserveFundPerUnitCents;
    }

    public function syndicShareTotalCents(): int
    {
        return $this->syndicShareTotalCents;
    }

    public function gasPricePerM3Cents(): ?int
    {
        return $this->gasPricePerM3Cents;
    }

    public function recordedAt(): DateTimeImmutable
    {
        return $this->recordedAt;
    }
}
