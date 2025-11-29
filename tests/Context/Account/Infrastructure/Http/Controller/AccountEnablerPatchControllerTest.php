<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Domain\Account;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class AccountEnablerPatchControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_enable_account(): void
    {
        // 1. Create a disabled account
        $account = AccountMother::create();
        // By default, accounts are created as inactive.

        $this->entityManager->persist($account);
        $this->entityManager->flush();

        $this->assertFalse($account->isActive());

        // 2. Send the PATCH request to enable it
        $this->client->request(
            'PATCH',
            '/api/v1/accounts/enable/' . $account->id()
        );

        // 3. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // 4. Assert the account is now active in the database
        $this->entityManager->clear();
        /** @var Account|null $enabledAccount */
        $enabledAccount = $this->entityManager->find(Account::class, $account->id());

        $this->assertNotNull($enabledAccount);
        $this->assertTrue($enabledAccount->isActive());
    }
}
