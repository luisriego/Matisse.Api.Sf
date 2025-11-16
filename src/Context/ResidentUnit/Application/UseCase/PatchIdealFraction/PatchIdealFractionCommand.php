<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\PatchIdealFraction;

use App\Shared\Application\Command;

final readonly class PatchIdealFractionCommand implements Command
{
    public function __construct(
        public string $id,
        public float $idealFraction,
    ) {}
}
