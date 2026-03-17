<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Infrastructure\Http\Controller;

use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class SlipGenerationPostControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createAuthenticatedClient();
    }

    public function test_it_should_generate_slips_and_return_created(): void
    {
        $payload = [
            'targetMonth' => '2024-08',
            'force' => false,
        ];

        $this->client->request(
            'POST',
            '/api/v1/slips/generation',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function test_it_should_return_bad_request_on_invalid_target_month_format(): void
    {
        $payload = [
            'targetMonth' => 'invalid-date',
        ];

        $this->client->request(
            'POST',
            '/api/v1/slips/generation',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
