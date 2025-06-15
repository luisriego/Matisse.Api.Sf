<?php

namespace App\Context\Account\Application\UseCase\UpdateAccount;

use App\Context\Account\Domain\AccountCode;
use App\Context\Account\Domain\AccountId;
use App\Context\Account\Domain\AccountName;
use App\Context\Account\Domain\AccountRepository;
use App\Shared\Application\EventBus;

final readonly class AccountUpdater
{
    public function __construct(private AccountRepository $accountRepository, private EventBus $bus) {}

    public function __invoke(AccountId $accountId, AccountCode $accountCode, AccountName $accountName): void
    {
        $account = $this->accountRepository->findOneByIdOrFail($accountId->value());

        $account->updateCode($accountCode);
        $account->updateName($accountName);

        $this->accountRepository->save($account);
        $this->bus->publish(...$account->pullDomainEvents());
    }
}