<?php

declare(strict_types=1);

namespace App\Context\Gas\Domain;

use App\Context\Gas\Domain\Bus\GasPriceWasDefined;
use App\Context\Gas\Domain\ValueObject\BufferPercentage;
use App\Context\Gas\Domain\ValueObject\CylinderCapacity;
use App\Context\Gas\Domain\ValueObject\GasAmount;
use App\Context\Gas\Domain\ValueObject\GasId;
use App\Shared\Domain\AggregateRoot;

final class Gas extends AggregateRoot
{
    public function __construct(
        private readonly GasId $id,
        private readonly float $pricePerM3,
    ) {}

    public static function definePrice(
        GasAmount $amount,
        CylinderCapacity $capacity,
        BufferPercentage $buffer,
    ): self {
        // The aggregate now operates on VOs, not primitives.
        // It asks the VOs for the values it needs in the correct format.
        $cylinderCapacityInM3 = $capacity->toM3();
        $billAmount = $amount->toFloat();

        $pricePerM3 = $billAmount / $cylinderCapacityInM3;

        $bufferFactor = $buffer->toFactor();

        if ($bufferFactor > 0) {
            $pricePerM3 += $pricePerM3 * $bufferFactor;
        }

        // --- Corrected Line ---
        // We create a new instance of GasId from a random Uuid value.
        $gasId = new GasId(GasId::random()->value());

        $gas = new self($gasId, $pricePerM3);

        // The aggregate records the event, fulfilling its main purpose.
        $gas->record(new GasPriceWasDefined(
            $gasId->value(),
            $pricePerM3,
        ));

        return $gas;
    }

    public function id(): GasId
    {
        return $this->id;
    }

    public function pricePerM3(): float
    {
        return $this->pricePerM3;
    }
}
