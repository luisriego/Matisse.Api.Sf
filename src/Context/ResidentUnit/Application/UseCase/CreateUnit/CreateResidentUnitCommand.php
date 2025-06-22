<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\CreateUnit;

use App\Shared\Application\Command;

final readonly class CreateResidentUnitCommand implements Command
{
    public function __construct(private string $id, private string $unit, private float $idealFraction) {}

    public function id(): string
    {
        return $this->id;
    }

    public function unit(): string
    {
        return $this->unit;
    }

    public function idealFraction(): float
    {
        return $this->idealFraction;
    }
}
