<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\SetInitialBalance;

use App\Context\Account\Domain\AccountRepository;
use App\Context\Account\Domain\Bus\InitialBalanceSet;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Event\EventBus;

readonly class SetInitialBalanceCommandHandler implements CommandHandler
{
    public function __construct(
        private AccountRepository $accountRepository,
        private EventBus $eventBus
    ) {
    }

    public function __invoke(SetInitialBalanceCommand $command): void
    {
        $account = $this->accountRepository->findOneByIdOrFail($command->accountId());

        $event = new InitialBalanceSet(
            $command->accountId(),
            $command->amount(),
            $command->date()
        );

        // In a complete Event Sourcing system, the event would be added to the aggregate
        // and persisted through the repository. For now, we publish it directly.
        $this->eventBus->publish($event);
    }
}
