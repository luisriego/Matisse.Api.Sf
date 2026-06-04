<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Infrastructure\Security;

use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final class SyndicWriteAuthorizationTest extends ApiTestCase
{
    public function testResidentCannotPerformWrites(): void
    {
        $this->createAuthenticatedClient(email: 'resident@example.com', asSyndic: false);

        $this->requestCreateAccount();

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testResidentCanRead(): void
    {
        $this->createAuthenticatedClient(email: 'reader@example.com', asSyndic: false);

        $this->client->request('GET', '/api/v1/accounts');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testSyndicCanPerformWrites(): void
    {
        $this->createAuthenticatedClient(email: 'syndic@example.com', asSyndic: true);

        $this->requestCreateAccount();

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    private function requestCreateAccount(): void
    {
        $payload = [
            'id'                   => UuidMother::create(),
            'name'                 => 'Authorization Test Account',
            'initialBalanceAmount' => 100_000,
            'initialBalanceDate'   => '2026-01-05',
        ];

        $this->client->request(
            'PUT',
            '/api/v1/accounts/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }
}
