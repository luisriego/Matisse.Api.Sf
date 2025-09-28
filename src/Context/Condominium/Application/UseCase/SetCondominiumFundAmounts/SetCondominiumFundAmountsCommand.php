<?php

declare(strict_types=1);

namespace App\Context\Condominium\Application\UseCase\SetCondominiumFundAmounts;

use App\Shared\Application\Command;

final readonly class SetCondominiumFundAmountsCommand implements Command
{
    public function __construct(
        private string $condominiumConfigurationId,
        private int $reserveFundAmount,
        private int $constructionFundAmount,
        private string $effectiveDate,
        private ?string $userId = null,
    ) {}

    public function condominiumConfigurationId(): string
    {
        return $this->condominiumConfigurationId;
    }

    public function reserveFundAmount(): int
    {
        return $this->reserveFundAmount;
    }

    public function constructionFundAmount(): int
    {
        return $this->constructionFundAmount;
    }

    public function effectiveDate(): string
    {
        return $this->effectiveDate;
    }

    public function userId(): ?string
    {
        return $this->userId;
    }
}
