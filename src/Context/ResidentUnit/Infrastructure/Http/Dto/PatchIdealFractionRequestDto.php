<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Http\Dto;

use App\Context\ResidentUnit\Application\UseCase\PatchIdealFraction\PatchIdealFractionCommand;

final readonly class PatchIdealFractionRequestDto
{
    public function __construct(
        public float $idealFraction,
    ) {}

    public function toCommand(string $id): PatchIdealFractionCommand
    {
        return new PatchIdealFractionCommand(
            $id,
            $this->idealFraction,
        );
    }
}
