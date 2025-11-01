<?php

declare(strict_types=1);

namespace App\Context\Gas\Infrastructure\Http\Dto;

use App\Context\Gas\Application\UseCase\CalculateGasPrice\DefineGasPriceCommand;

final readonly class DefineGasPriceRequestDto
{
    public function __construct(
        public int $billAmountInCents,
        public ?int $cylinderCapacityInKg = null,
        public ?int $bufferPercentage = null,
    ) {}

    public function toCommand(): DefineGasPriceCommand
    {
        return new DefineGasPriceCommand(
            $this->billAmountInCents,
            $this->cylinderCapacityInKg,
            $this->bufferPercentage,
        );
    }
}
