<?php

namespace App\Context\Account\Application\UseCase\EnableAccount;

use App\Context\Account\Domain\AccountRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Application\EventBus;

readonly class EnableAccountCommandHandler implements  CommandHandler
{
    public function __construct(private AccountRepository $accountRepository, private EventBus $eventBus) {}

    public function __invoke(EnableAccountCommand $command): void
    {
        $account = $this->accountRepository->findOneByIdOrFail($command->id());

        $account->enable();

        $this->accountRepository->save($account);
        $this->eventBus->publish(...$account->pullDomainEvents());
    }
}