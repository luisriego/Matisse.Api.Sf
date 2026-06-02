<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Dto;

final readonly class ExpectedExpenseCreateOrUpdateDto
{
    /**
     * @param list<int>|null $monthsOfYear required when frequency is custom; optional override otherwise
     */
    public function __construct(
        public string $displayName,
        public string $frequency,
        public string $amountKind,
        public ?array $monthsOfYear = null,
        public ?int $dueDay = null,
    ) {}
}
