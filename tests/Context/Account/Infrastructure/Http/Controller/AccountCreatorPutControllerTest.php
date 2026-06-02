<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Domain\Account;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final class AccountCreatorPutControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function testItShouldCreateAccount(): void
    {
        $accountId = UuidMother::create();

        $payload = [
            'id'                     => $accountId,
            'name'                   => 'Test Account',
            'initialBalanceAmount'   => 250_000,
            'initialBalanceDate'     => '2026-01-05',
        ];

        $this->client->request(
            'PUT',
            '/api/v1/accounts/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Assert that the account was correctly created in the database
        $this->entityManager->clear();
        /** @var Account|null $createdAccount */
        $createdAccount = $this->entityManager->find(Account::class, $accountId);

        $this->assertNotNull($createdAccount);
        $this->assertEquals($payload['name'], $createdAccount->name());

        $this->client->request('GET', '/api/v1/accounts/' . $accountId . '/balance');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $balancePayload = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(250_000, $balancePayload['balance']);
    }

    public function testItReturns400WhenInitialBalanceIsMissing(): void
    {
        $payload = [
            'id'   => UuidMother::create(),
            'name' => 'Test Account',
        ];

        $this->client->request(
            'PUT',
            '/api/v1/accounts/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
