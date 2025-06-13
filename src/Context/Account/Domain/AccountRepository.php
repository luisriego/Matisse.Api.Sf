<?php

declare(strict_types=1);

namespace App\Context\Account\Domain;

interface AccountRepository
{
    public function save(Account $account, bool $flush = true): void;
    public function findOneByIdOrFail(string $id): Account;
}