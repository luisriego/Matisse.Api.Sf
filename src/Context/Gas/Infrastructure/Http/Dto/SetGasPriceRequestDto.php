<?php

declare(strict_types=1);

namespace App\Context\Gas\Infrastructure\Http\Dto;

use App\Context\Gas\Application\UseCase\SetGasPrice\SetGasPriceCommand;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class SetGasPriceRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $pricePerM3InCents,
    ) {}

    public function toCommand(): SetGasPriceCommand
    {
        return new SetGasPriceCommand(
            $this->pricePerM3InCents,
        );
    }
}
