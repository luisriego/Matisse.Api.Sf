<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Infrastructure\Http\Controller;

use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class GetAllAccountsControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_return_all_accounts(): void
    {
        // 1. Create a couple of accounts to ensure the list is not empty
        $account1 = AccountMother::create();
        $account2 = AccountMother::create();

        $this->entityManager->persist($account1);
        $this->entityManager->persist($account2);
        $this->entityManager->flush();

        // 2. Send the GET request
        $this->client->request('GET', '/api/v1/accounts');

        // 3. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        // 4. Assert the content of the response
        $this->assertIsArray($data);
        $this->assertArrayHasKey('accounts', $data);
        $this->assertArrayHasKey('qtd', $data);
        $this->assertEquals(2, $data['qtd']);

        $accountsInResponse = $data['accounts'];
        $this->assertCount(2, $accountsInResponse);

        // Check that the IDs of the created accounts are in the response
        $responseIds = array_column($accountsInResponse, 'id');
        $this->assertContains($account1->id(), $responseIds);
        $this->assertContains($account2->id(), $responseIds);
    }
}
