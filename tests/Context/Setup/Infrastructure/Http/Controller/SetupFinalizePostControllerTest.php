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

    public function testItReturnsBadRequestWhenCoreSetupNotSatisfied(): void
    {
        $this->client->request('POST', '/api/v1/setup/finalize');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
