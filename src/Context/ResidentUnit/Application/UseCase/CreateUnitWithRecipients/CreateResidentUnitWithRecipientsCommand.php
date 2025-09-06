<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\UseCase\CreateUnitWithRecipients;

use App\Shared\Application\Command;

final readonly class CreateResidentUnitWithRecipientsCommand implements Command
{
    public function __construct(
        private string $id,
        private string $unit,
        private float $idealFraction,
        private array $notificationRecipients
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

    public function notificationRecipients(): array
    {
        return $this->notificationRecipients;
    }
}
