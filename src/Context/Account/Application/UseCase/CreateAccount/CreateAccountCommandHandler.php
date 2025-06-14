<?php

namespace App\Context\Account\Application\UseCase\CreateAccount;

use App\Context\Account\Domain\AccountCode;
use App\Context\Account\Domain\AccountId;
use App\Context\Account\Domain\AccountName;
use App\Shared\Application\CommandHandler;

class CreateAccountCommandHandler implements CommandHandler
{
    public function __construct()
    {
    }

    public function __invoke(CreateAccountCommand $command): void
    {
        $id = new AccountId($command->id());
        $code = new AccountCode($command->code());
        $name = new AccountName($command->name());


    }
}