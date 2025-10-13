<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\SetInitialBalance;

use App\Context\Account\Domain\AccountRepository;
use App\Context\Account\Domain\Bus\InitialBalanceSet;
use App\Shared\Application\CommandHandler;
use App\Shared\Application\EventStore;

 // Changed from EventBus

readonly class SetInitialBalanceCommandHandler implements CommandHandler
{
    public function __construct(
        private AccountRepository $accountRepository,
        private EventStore $eventStore, // Changed from EventBus
    ) {}

    public function __invoke(SetInitialBalanceCommand $command): void
    {
        $account = $this->accountRepository->findOneByIdOrFail($command->accountId());

        $event = new InitialBalanceSet(
            $command->accountId(),
            $command->amount(),
            $command->date(),
        );

        // Append the event directly to the EventStore for persistence.
        $this->eventStore->append($event);
    }
}
