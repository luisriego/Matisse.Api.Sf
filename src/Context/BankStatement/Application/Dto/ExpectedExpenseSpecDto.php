<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\Dto;

final readonly class ExpectedExpenseSpecDto
{
    public function __construct(
        public ?string $recurringExpenseId,
        public ?ExpectedExpenseCreateOrUpdateDto $createOrUpdate,
    ) {}
}
