<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Domain;

use App\Context\Account\Domain\Account;
use PHPUnit\Framework\TestCase;

final class AccountTest extends TestCase
{
    public function testItShouldCreateWithDescription(): void
    {
        $id = AccountIdMother::create();
        $name = AccountNameMother::create();
        $description = AccountDescriptionMother::create();

        $account = Account::createWithDescription($id, $name, $description);

        self::assertEquals($description->value(), $account->description());
    }

    public function testItSetsCreatedAtOnCreation(): void
    {
        $account = AccountMother::create();

        self::assertNotNull($account->createdAt());
    }

    public function testItSetsUpdatedAtOnUpdate(): void
    {
        $account = AccountMother::create();

        self::assertNull($account->updatedAt());

        $newName = AccountNameMother::create();
        $newDescription = AccountDescriptionMother::create();
        $account->updateDetails($newName, $newDescription);

        self::assertNotNull($account->updatedAt());
    }
}
