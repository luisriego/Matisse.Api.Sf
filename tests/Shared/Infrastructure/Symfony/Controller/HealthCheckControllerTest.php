<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Symfony\Controller;

use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;

use const JSON_THROW_ON_ERROR;

final class HealthCheckControllerTest extends ApiTestCase
{
    public function testItReturnsHealthEndpoint(): void
    {
        $this->client->request('GET', '/api/health');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('healthy', $data['status']);
        self::assertArrayHasKey('dependencies', $data);
        self::assertIsArray($data['dependencies']);
        self::assertArrayHasKey('setup', $data['dependencies']);
    }

    public function testItDoesNotRequireAuthentication(): void
    {
        // No createAuthenticatedClient() call — direct request without credentials
        $this->client->request('GET', '/api/health');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
