<?php

namespace App\Tests\Context\Account\Domain;

use App\Context\Account\Domain\Account;
use PHPUnit\Framework\TestCase;

class CreateAccountTest extends TestCase
{
    public function test_it_should_create_an_account_with_valid_data(): void
    {
        $account = AccountMother::create();

        $this->assertInstanceOf(Account::class, $account);
        $this->assertNotEmpty($account->id());
        $this->assertNotEmpty($account->code());
        $this->assertNotEmpty($account->name());
    }
}