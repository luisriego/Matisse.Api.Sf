<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Application\UseCase\CreateAccount;

use App\Context\Account\Application\UseCase\CreateAccount\AccountCreator;
use App\Context\Account\Application\UseCase\CreateAccount\CreateAccountCommand;
use App\Context\Account\Application\UseCase\CreateAccount\CreateAccountCommandHandler;
use App\Context\Account\Domain\AccountCode;
use App\Context\Account\Domain\AccountId;
use App\Context\Account\Domain\AccountName;
use App\Tests\Context\Account\AccountModuleUnitTestCase;
use App\Tests\Context\Account\Domain\AccountCodeMother;
use App\Tests\Context\Account\Domain\AccountIdMother;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Account\Domain\AccountNameMother;

final class CreateAccountCommandHandlerTest extends AccountModuleUnitTestCase
{
    private CreateAccountCommandHandler $handler;
    private AccountCreator $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creator = new AccountCreator($this->repository(), $this->eventBus());
        $this->handler = new CreateAccountCommandHandler($this->creator);
    }

    /** @test  */
    public function test_it_should_create_an_account(): void
    {
        $id = AccountIdMother::create();
        $code = AccountCodeMother::create();
        $name = AccountNameMother::create();

        $command = new CreateAccountCommand(
            $id->value(),
            $code->value(),
            $name->value()
        );

        // Create actual Account object
        $account = AccountMother::create(
            new AccountId($command->id()),
            new AccountCode($command->code()),
            new AccountName($command->name())
        );

        // Pass the Account object directly
        $this->shouldSave($account);
        $this->shouldPublishDomainEvents([]);

        $this->handler->__invoke($command);
    }
}