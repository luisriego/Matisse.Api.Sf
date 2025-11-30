<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Domain\Account;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class AccountDisablerPatchControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_disable_account(): void
    {
        // 1. Create an enabled account
        $account = AccountMother::create();
        $account->enable(); // Enable it first

        $this->entityManager->persist($account);
        $this->entityManager->flush();

        $this->assertTrue($account->isActive());

        // 2. Send the PATCH request to disable it
        $this->client->request(
            'PATCH',
            '/api/v1/accounts/disable/' . $account->id()
        );

        // 3. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // 4. Assert the account is now inactive in the database
        $this->entityManager->clear();
        /** @var Account|null $disabledAccount */
        $disabledAccount = $this->entityManager->find(Account::class, $account->id());

        $this->assertNotNull($disabledAccount);
        $this->assertFalse($disabledAccount->isActive());
    }
}
