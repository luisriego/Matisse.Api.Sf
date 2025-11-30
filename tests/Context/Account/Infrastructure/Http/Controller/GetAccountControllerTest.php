<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Infrastructure\Http\Controller;

use App\Context\Account\Domain\Exception\AccountNotFoundException;
use App\Context\Account\Infrastructure\Http\Controller\GetAccountController;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \App\Context\Account\Infrastructure\Http\Controller\GetAccountController
 */
final class GetAccountControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function test_it_should_return_account_when_found(): void
    {
        // 1. Create an account to be found
        $account = AccountMother::create();
        $this->entityManager->persist($account);
        $this->entityManager->flush();

        // 2. Send the GET request
        $this->client->request('GET', '/api/v1/accounts/' . $account->id());

        // 3. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        $this->assertIsArray($data);
        $this->assertEquals($account->id(), $data['id']);
        $this->assertEquals($account->code(), $data['code']);
        $this->assertEquals($account->name(), $data['name']);
    }

    public function test_it_should_return_not_found_when_account_does_not_exist(): void
    {
        // 1. Generate a random non-existent ID
        $nonExistentId = UuidMother::create();

        // 2. Send the GET request
        $this->client->request('GET', '/api/v1/accounts/' . $nonExistentId);

        // 3. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_it_maps_exceptions_correctly(): void
    {
        $controller = $this->getContainer()->get(GetAccountController::class);
        $exceptions = $controller->exceptions();

        $this->assertArrayHasKey(AccountNotFoundException::class, $exceptions);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $exceptions[AccountNotFoundException::class]);
    }
}
