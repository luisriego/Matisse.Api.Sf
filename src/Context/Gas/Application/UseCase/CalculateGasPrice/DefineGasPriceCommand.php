<?php

declare(strict_types=1);

namespace App\Context\Gas\Application\UseCase\CalculateGasPrice;

use App\Shared\Application\Command;

final readonly class DefineGasPriceCommand implements Command
{
    public function __construct(
        private int $billAmountInCents,
        private ?int $cylinderCapacityInKg,
        private ?int $bufferPercentage,
    ) {}

    public function getBillAmountInCents(): int
    {
        return $this->billAmountInCents;
    }

    public function getCylinderCapacityInKg(): ?int
    {
        return $this->cylinderCapacityInKg;
    }

    public function getBufferPercentage(): ?int
    {
        return $this->bufferPercentage;
    }
}
