<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Domain\Account;
use App\Tests\Context\Account\Domain\AccountCodeMother;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class AccountUpdaterPatchControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_update_account(): void
    {
        // 1. Create an initial account to be updated
        $account = AccountMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        // 2. Define the update payload
        $updatedCode = AccountCodeMother::create()->value();
        $payload = [
            'code' => $updatedCode,
            'name' => 'Updated Account Name',
            'description' => 'Updated account description.',
        ];

        // 3. Send the PATCH request
        $this->client->request(
            'PATCH',
            '/api/v1/accounts/' . $account->id(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // 5. Assert the changes in the database
        $this->entityManager->clear();
        /** @var Account|null $updatedAccount */
        $updatedAccount = $this->entityManager->find(Account::class, $account->id());

        $this->assertNotNull($updatedAccount);
        $this->assertEquals($payload['code'], $updatedAccount->code());
        $this->assertEquals($payload['name'], $updatedAccount->name());
        $this->assertEquals($payload['description'], $updatedAccount->description());
    }
}
