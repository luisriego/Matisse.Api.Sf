<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Domain;

interface BankTransactionImportRepository
{
    public function flush(): void;

    public function save(BankTransactionImport $import, bool $flush = true): void;

    public function existsByImportLineKey(string $importLineKey, string $bankAccountId): bool;

    /**
     * @return string[] import line keys already persisted for the given bank account
     */
    public function findImportedLineKeys(string $bankAccountId, array $importLineKeys): array;
}
