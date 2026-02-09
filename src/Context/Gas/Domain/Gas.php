<?php

declare(strict_types=1);

namespace App\Context\Gas\Domain;

use App\Context\Gas\Domain\Bus\GasPriceWasDefined;
use App\Context\Gas\Domain\Bus\GasReadingWasRecorded;
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

final class Gas extends AggregateRoot
{
    public function __construct(
        private readonly GasId $id,
        private readonly ?int $pricePerM3 = null,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public static function definePrice(
        GasAmount $amount,
        CylinderCapacity $capacity,
        BufferPercentage $buffer,
    ): self {
        $pricePerM3 = (int) (($amount->toInt() * 2) / $capacity->value());

        $bufferValue = $buffer->value();

        if ($bufferValue > 0) {
            $pricePerM3 += (int) (($pricePerM3 * $bufferValue) / 100);
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
     * @throws DateMalformedStringException
     */
    public static function recordReading(
        GasId $id,
        ResidentUnitId $residentUnitId,
        Year $year,
        Month $month,
        ReadingInM3 $reading,
        ?int $price,
    ): self {
        $gas = new self($id);
        $gas->record(new GasReadingWasRecorded(
            $id->value(),
            $residentUnitId->value(),
            $year->value(),
            $month->value(),
            $reading->value(),
            $price,
        ));

        return $gas;
    }

    public function id(): GasId
    {
        return $this->id;
    }

    public function pricePerM3(): ?int
    {
        return $this->pricePerM3;
    }
}
