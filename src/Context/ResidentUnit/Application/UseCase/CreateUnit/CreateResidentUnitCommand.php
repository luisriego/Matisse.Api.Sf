<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\CreateUnit;

use App\Shared\Application\Command;

final readonly class CreateResidentUnitCommand implements Command
{
    public function __construct(
        private string $id,
        private string $unit,
        private float $idealFraction,
        private string $email,
        private ?string $name = null,
    ) {}

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

    public function email(): string
    {
        return $this->email;
    }

    public function name(): ?string
    {
        return $this->name;
    }
}
