<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\CreateAccount;

use App\Context\Account\Domain\AccountId;
use App\Context\Account\Domain\AccountName;
use App\Context\Account\Domain\Event\InitialBalanceSet;
use App\Shared\Application\CommandHandler;
use App\Shared\Application\EventStore;

readonly class CreateAccountCommandHandler implements CommandHandler
{
    public function __construct(
        private AccountCreator $creator,
        private EventStore $eventStore,
    ) {}

    public function __invoke(CreateAccountCommand $command): void
    {
        $id = new AccountId($command->id());
        $name = new AccountName($command->name());

        $this->creator->__invoke($id, $name);

        $this->eventStore->append(new InitialBalanceSet(
            $command->id(),
            $command->initialBalanceAmount(),
            $command->initialBalanceDate(),
        ));
    }
}
