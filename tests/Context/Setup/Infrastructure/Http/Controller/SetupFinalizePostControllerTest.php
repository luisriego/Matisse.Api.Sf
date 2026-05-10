<?php

declare(strict_types=1);

namespace App\Tests\Context\Setup\Infrastructure\Http\Controller;

use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class SetupFinalizePostControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_returns_bad_request_when_core_setup_not_satisfied(): void
    {
        $this->client->request('POST', '/api/v1/setup/finalize');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
