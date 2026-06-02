<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Application\UseCase\CreateAccount;

use App\Context\Account\Application\UseCase\CreateAccount\AccountCreator;
use App\Tests\Context\Account\AccountModuleUnitTestCase;
use App\Tests\Context\Account\Domain\AccountIdMother;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Account\Domain\AccountNameMother;

final class AccountCreatorTest extends AccountModuleUnitTestCase
{
    private AccountCreator $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creator = new AccountCreator($this->repository());
    }

    /**
     * @test
     */
    public function testItShouldCreateAnAccount(): void
    {
        $id = AccountIdMother::create();
        $name = AccountNameMother::create();

        $account = AccountMother::create($id, $name);

        $this->shouldSave($account);

        $this->creator->__invoke($id, $name);
    }
}
