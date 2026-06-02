<?php

declare(strict_types=1);

namespace App\Context\Gas\Domain;

use App\Context\Gas\Domain\Event\GasPriceWasDefined;
use App\Context\Gas\Domain\Event\GasReadingWasRecorded;
use App\Context\Gas\Domain\ValueObject\BufferPercentage;
use App\Context\Gas\Domain\ValueObject\CylinderCapacity;
use App\Context\Gas\Domain\ValueObject\GasAmount;
use App\Context\Gas\Domain\ValueObject\GasId;
use App\Context\Gas\Domain\ValueObject\ReadingInM3;
use App\Context\ResidentUnit\Domain\ResidentUnitId;
use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\ValueObject\Month;
use App\Shared\Domain\ValueObject\Year;
use DateMalformedStringException;

use function round;

final class Gas extends AggregateRoot
{
    public function __construct(
        private readonly GasId $id,
        private readonly ?int $pricePerM3InCents = null,
    ) {}

    /**
     * Bill in cents, cylinder capacity in kg (m³ = kg/2). Price per m³ in cents:
     * (2 × billCents × (100 + bufferPct)) ÷ (kg × 100), rounded.
     *
     * @throws DateMalformedStringException
     */
    public static function definePrice(
        GasAmount $amount,
        CylinderCapacity $capacity,
        BufferPercentage $buffer,
    ): self {
        $billCents = $amount->value();
        $kg = $capacity->value();
        $bufferPct = $buffer->value();

        $pricePerM3InCents = self::calculatePricePerM3InCentsFromBillAndCylinder($billCents, $kg, $bufferPct);

        $gasId = new GasId(GasId::random()->value());

        $gas = new self($gasId, $pricePerM3InCents);

        $gas->record(new GasPriceWasDefined(
            $gasId->value(),
            $pricePerM3InCents,
        ));

        return $gas;
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function setDirectPrice(int $pricePerM3InCents): self
    {
        $gasId = new GasId(GasId::random()->value());

        $gas = new self($gasId, $pricePerM3InCents);

        $gas->record(new GasPriceWasDefined(
            $gasId->value(),
            $pricePerM3InCents,
        ));

        return $gas;
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function recordReading(
        GasId $id,
        ResidentUnitId $residentUnitId,
        Year $year,
        Month $month,
        ReadingInM3 $reading,
    ): self {
        $gas = new self($id);
        $gas->record(new GasReadingWasRecorded(
            $id->value(),
            $residentUnitId->value(),
            $year->value(),
            $month->value(),
            $reading->value(),
        ));

        return $gas;
    }

    public function id(): GasId
    {
        return $this->id;
    }

    public function pricePerM3InCents(): ?int
    {
        return $this->pricePerM3InCents;
    }

    private static function calculatePricePerM3InCentsFromBillAndCylinder(
        int $billCents,
        int $cylinderCapacityKg,
        int $bufferPercentage,
    ): int {
        return (int) round((2 * $billCents * (100 + $bufferPercentage)) / ($cylinderCapacityKg * 100));
    }
}
