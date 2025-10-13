<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\CreateAccount;

use App\Context\Account\Domain\Account;
use App\Context\Account\Domain\AccountCode;
use App\Context\Account\Domain\AccountId;
use App\Context\Account\Domain\AccountName;
use App\Context\Account\Domain\AccountRepository;

final readonly class AccountCreator
{
    public function __construct(private AccountRepository $accountRepository) {}

    public function __invoke(AccountId $accountId, AccountCode $accountCode, AccountName $accountName): void
    {
        $account = Account::create($accountId, $accountCode, $accountName);

        $this->accountRepository->save($account);
    }
}
