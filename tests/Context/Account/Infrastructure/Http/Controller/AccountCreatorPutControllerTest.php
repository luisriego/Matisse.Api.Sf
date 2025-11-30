<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Domain\Account;
use App\Tests\Context\Account\Domain\AccountCodeMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class AccountCreatorPutControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_create_account(): void
    {
        $accountId = UuidMother::create();
        $accountCode = AccountCodeMother::create()->value();

        $payload = [
            'id' => $accountId,
            'code' => $accountCode,
            'name' => 'Test Account',
        ];

        $this->client->request(
            'PUT',
            '/api/v1/accounts/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Assert that the account was correctly created in the database
        $this->entityManager->clear();
        /** @var Account|null $createdAccount */
        $createdAccount = $this->entityManager->find(Account::class, $accountId);

        $this->assertNotNull($createdAccount);
        $this->assertEquals($payload['code'], $createdAccount->code());
        $this->assertEquals($payload['name'], $createdAccount->name());
    }
}
