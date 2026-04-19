<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Domain;

interface BankTransactionImportRepository
{
    public function flush(): void;

    public function save(BankTransactionImport $import, bool $flush = true): void;

    public function existsByFitId(string $fitId, string $bankAccountId): bool;

    /** @return string[] fitIds already imported for the given bank account */
    public function findImportedFitIds(string $bankAccountId, array $fitIds): array;
}
