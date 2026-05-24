<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\UpdateAccount;

use App\Context\Account\Domain\AccountDescription;
use App\Context\Account\Domain\AccountId;
use App\Context\Account\Domain\AccountName;
use App\Context\Account\Domain\AccountRepository;

final readonly class AccountUpdater
{
    public function __construct(private AccountRepository $accountRepository) {}

    public function __invoke(
        AccountId $accountId,
        AccountName $accountName,
        AccountDescription $accountDescription,
    ): void {
        $account = $this->accountRepository->findOneByIdOrFail($accountId->value());

        $account->updateDetails($accountName, $accountDescription);

        $this->accountRepository->save($account, true);
    }
}
