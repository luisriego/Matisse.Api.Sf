<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Records that a bank transaction line (stable import line key, e.g. OFX FITID) was successfully
 * imported either as an Expense (DEBIT) or as an Income (CREDIT),
 * preventing duplicate imports.
 *
 * Exactly one of {expenseId, incomeId} MUST be set.
 */
class BankTransactionImport
{
    private string $id;
    private string $importLineKey;
    private string $bankAccountId;
    private DateTimeImmutable $importedAt;
    private ?string $expenseId;
    private ?string $incomeId;

    public function __construct(
        string $id,
        string $importLineKey,
        string $bankAccountId,
        ?string $expenseId = null,
        ?string $incomeId = null,
    ) {
        if (($expenseId === null) === ($incomeId === null)) {
            throw new InvalidArgumentException(
                'BankTransactionImport must reference exactly one of expenseId or incomeId.',
            );
        }

        $this->id             = $id;
        $this->importLineKey  = $importLineKey;
        $this->bankAccountId  = $bankAccountId;
        $this->expenseId     = $expenseId;
        $this->incomeId      = $incomeId;
        $this->importedAt    = new DateTimeImmutable();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function importLineKey(): string
    {
        return $this->importLineKey;
    }

    public function bankAccountId(): string
    {
        return $this->bankAccountId;
    }

    public function expenseId(): ?string
    {
        return $this->expenseId;
    }

    public function incomeId(): ?string
    {
        return $this->incomeId;
    }

    public function importedAt(): DateTimeImmutable
    {
        return $this->importedAt;
    }
}
