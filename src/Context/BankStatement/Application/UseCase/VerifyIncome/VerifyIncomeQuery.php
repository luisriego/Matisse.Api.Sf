<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\UseCase\VerifyIncome;

use App\Context\BankStatement\Application\Dto\CreditLineDto;
use App\Shared\Application\Query;

final readonly class VerifyIncomeQuery implements Query
{
    /**
     * @param CreditLineDto[] $creditLines CREDIT lines from the OFX to count as received income.
     */
    public function __construct(
        public readonly int   $month,
        public readonly int   $year,
        public readonly array $creditLines,
    ) {}
}
