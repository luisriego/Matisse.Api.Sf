<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\CreateAccount;

use App\Context\Account\Domain\AccountCode;
use App\Context\Account\Domain\AccountDescription;
use App\Context\Account\Domain\AccountId;
use App\Context\Account\Domain\AccountName;
use App\Shared\Application\CommandHandler;

use function is_string;

readonly class CreateAccountCommandHandler implements CommandHandler
{
    public function __construct(private AccountCreator $creator) {}

    public function __invoke(CreateAccountCommand $command): void
    {
        $id = new AccountId($command->id());
        $code = new AccountCode($command->code());
        $name = new AccountName($command->name());

        $accountDescription = null;
        $commandDescriptionValue = $command->description();

        if (is_string($commandDescriptionValue) && $commandDescriptionValue !== '') {
            $accountDescription = new AccountDescription($commandDescriptionValue);
        }

        $this->creator->__invoke($id, $code, $name, $accountDescription);
    }
}
