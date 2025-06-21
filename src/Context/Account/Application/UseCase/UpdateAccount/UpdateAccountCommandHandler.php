<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\UpdateAccount;

use App\Context\Account\Domain\AccountCode;
use App\Context\Account\Domain\AccountDescription;
use App\Context\Account\Domain\AccountId;
use App\Context\Account\Domain\AccountName;
use App\Shared\Application\CommandHandler;

final readonly class UpdateAccountCommandHandler implements CommandHandler
{
    public function __construct(private AccountUpdater $updater) {}

    public function __invoke(UpdateAccountCommand $command): void
    {
        $id = new AccountId($command->id());
        $code = new AccountCode($command->code());
        $name = new AccountName($command->name());
        $description = new AccountDescription($command->description());


        $this->updater->__invoke($id, $code, $name, $description);
    }
}
