<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Domain;

use App\Context\Account\Domain\Account;
use App\Context\Account\Domain\AccountDescription;
use App\Tests\Shared\Domain\MotherCreator;
use PHPUnit\Framework\TestCase;

final class AccountTest extends TestCase
{
    public function test_it_should_create_with_description(): void
    {
        $id = AccountIdMother::create();
        $code = AccountCodeMother::create();
        $name = AccountNameMother::create();
        // Use MotherCreator to generate a sentence, ensuring it's long enough.
        $descriptionText = MotherCreator::random()->sentence(3);
        $description = new AccountDescription($descriptionText);

        $account = Account::createWithDescription($id, $code, $name, $description);

        self::assertEquals($descriptionText, $account->description());
    }

    public function test_it_sets_created_at_on_creation(): void
    {
        $account = AccountMother::create();

        self::assertNotNull($account->createdAt());
    }

    public function test_it_sets_updated_at_on_update(): void
    {
        $account = AccountMother::create();

        self::assertNull($account->updatedAt());

        $newName = AccountNameMother::create();
        $account->updateName($newName);

        self::assertNotNull($account->updatedAt());
    }
}
