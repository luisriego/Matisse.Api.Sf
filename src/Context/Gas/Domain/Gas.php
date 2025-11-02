<?php

declare(strict_types=1);

namespace App\Context\Gas\Domain;

use App\Context\Gas\Domain\Bus\GasConsumptionWasRecorded;
use App\Context\Gas\Domain\Bus\GasPriceWasDefined;
use App\Context\Gas\Domain\ValueObject\BufferPercentage;
use App\Context\Gas\Domain\ValueObject\ConsumptionInM3;
use App\Context\Gas\Domain\ValueObject\CylinderCapacity;
use App\Context\Gas\Domain\ValueObject\GasAmount;
use App\Context\Gas\Domain\ValueObject\GasId;
use App\Context\ResidentUnit\Domain\ResidentUnitId;
use App\Shared\Domain\AggregateRoot;

final class Gas extends AggregateRoot
{
    public function __construct(
        private readonly GasId $id,
        private readonly ?float $pricePerM3 = null
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     */
    public static function definePrice(
        GasAmount $amount,
        CylinderCapacity $capacity,
        BufferPercentage $buffer
    ): self {
        $cylinderCapacityInM3 = $capacity->toM3();
        $billAmount = $amount->toFloat();

        $pricePerM3 = $billAmount / $cylinderCapacityInM3;

        $bufferFactor = $buffer->toFactor();

        if ($bufferFactor > 0) {
            $pricePerM3 += $pricePerM3 * $bufferFactor;
        }

        $gasId = new GasId(GasId::random()->value());

        $gas = new self($gasId, $pricePerM3);

        $gas->record(new GasPriceWasDefined(
            $gasId->value(),
            $pricePerM3,
        ));

        return $gas;
    }

    /**
     * @throws \DateMalformedStringException
     */
    public static function recordConsumption(
        GasId $id,
        ResidentUnitId $residentUnitId,
        int $year,
        int $month,
        ConsumptionInM3 $consumption
    ): self {
        $gas = new self($id);

        $gas->record(new GasConsumptionWasRecorded(
            $id->value(),
            $residentUnitId->value(),
            $year,
            $month,
            $consumption->value()
        ));

        return $gas;
    }


    public function id(): GasId
    {
        return $this->id;
    }

    public function pricePerM3(): ?float
    {
        return $this->pricePerM3;
    }
}