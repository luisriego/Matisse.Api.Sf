<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\DisableAccount;

use App\Context\Account\Domain\AccountRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Application\EventBus;

readonly class DisableAccountCommandHandler implements CommandHandler
{
    public function __construct(private AccountRepository $repository, private EventBus $eventBus) {}

    public function __invoke(DisableAccountCommand $command): void
    {
        $account = $this->repository->findOneByIdOrFail($command->id());

        $account->disable();

        $this->repository->save($account);
        $this->eventBus->publish(...$account->pullDomainEvents());
    }
}
