<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Domain;

use App\Shared\Domain\ValueObject\DateTimeValueObject;

use function array_filter;
use function array_values;

final readonly class ParsedBankStatement
{
    /**
     * @param BankTransaction[] $transactions
     */
    public function __construct(
        public readonly string $bankId,
        public readonly string $accountId,
        public readonly string $currency,
        public readonly DateTimeValueObject $periodStart,
        public readonly DateTimeValueObject $periodEnd,
        public readonly array $transactions,
        public readonly ?int $ledgerBalanceInCents,
        public readonly ?DateTimeValueObject $ledgerBalanceDate,
    ) {}

    /**
     * @return BankTransaction[]
     */
    public function debits(): array
    {
        return array_values(array_filter(
            $this->transactions,
            static fn (BankTransaction $t) => $t->isDebit(),
        ));
    }

    /**
     * @return BankTransaction[]
     */
    public function credits(): array
    {
        return array_values(array_filter(
            $this->transactions,
            static fn (BankTransaction $t) => $t->isCredit(),
        ));
    }
}
