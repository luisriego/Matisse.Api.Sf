<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Domain;

use App\Shared\Domain\ValueObject\DateTimeValueObject;

use function abs;

final readonly class BankTransaction
{
    /**
     * @param string              $importLineKey chave técnica estável da linha no extrato (idempotência); não é conta contábil
     * @param string              $bankAccountId Bank account number from OFX ACCTID
     * @param string              $type          DEBIT or CREDIT
     * @param int                 $amountInCents Signed amount in cents (negative for DEBIT)
     * @param DateTimeValueObject $postedAt      Posting date (calendar day only; time ignored)
     * @param string              $memo          Raw description from bank (OFX MEMO)
     */
    public function __construct(
        public readonly string $importLineKey,
        public readonly string $bankAccountId,
        public readonly string $type,
        public readonly int $amountInCents,
        public readonly DateTimeValueObject $postedAt,
        public readonly string $memo,
    ) {}

    public function isDebit(): bool
    {
        return $this->type === 'DEBIT';
    }

    public function isCredit(): bool
    {
        return $this->type === 'CREDIT';
    }

    public function absAmountInCents(): int
    {
        return (int) abs($this->amountInCents);
    }
}
