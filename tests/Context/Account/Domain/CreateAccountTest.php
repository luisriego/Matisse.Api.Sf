<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Domain;

use App\Context\Account\Domain\Account;
use PHPUnit\Framework\TestCase;

class CreateAccountTest extends TestCase
{
    public function testItShouldCreateAnAccountWithValidData(): void
    {
        $account = AccountMother::create();

        $this->assertInstanceOf(Account::class, $account);
        $this->assertNotEmpty($account->id());
        $this->assertNotEmpty($account->name());
    }
}
