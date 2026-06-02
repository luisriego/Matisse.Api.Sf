<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Infrastructure\Http\Controller;

use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;

final class ExplainSlipGenerationGetControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createAuthenticatedClient();
    }

    public function testItReturnsExplainPayloadForValidTargetMonth(): void
    {
        $this->client->request(
            'GET',
            '/api/v1/slips/generation/explain?targetMonth=2024-08',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('targetMonth', $data);
        $this->assertSame('2024-08', $data['targetMonth']);
    }

    public function testItReturnsBadRequestOnInvalidTargetMonth(): void
    {
        $this->client->request(
            'GET',
            '/api/v1/slips/generation/explain?targetMonth=invalid',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
