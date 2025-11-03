<?php

declare(strict_types=1);

namespace App\Tests\Context\Gas\Application\UseCase\CalculateGasPrice;

use App\Context\Gas\Application\UseCase\CalculateGasPrice\DefineGasPriceCommand;

final class DefineGasPriceCommandMother
{
    public static function create(
        ?int $billAmountInCents = null,
        ?int $cylinderCapacityInKg = null,
        ?int $bufferPercentage = null
    ): DefineGasPriceCommand {
        return new DefineGasPriceCommand(
            $billAmountInCents ?? 10000, // Default 100.00
            $cylinderCapacityInKg ?? 45, // Default 45kg
            $bufferPercentage ?? 10 // Default 10%
        );
    }
}
