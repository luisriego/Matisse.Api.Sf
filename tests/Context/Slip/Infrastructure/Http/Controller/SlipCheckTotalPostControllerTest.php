<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Infrastructure\Http\Controller;

use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \App\Context\Slip\Infrastructure\Http\Controller\SlipCheckTotalPostController
 */
final class SlipCheckTotalPostControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }
    
    protected function tearDown(): void
    {
        $this->client = null;
        parent::tearDown();
    }

    public function test_it_should_return_ok_when_total_is_within_range(): void
    {
        $payload = ['amount' => 750000];

        $this->client->request(
            'POST',
            '/api/v1/slips/check-total',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('ok', $responseContent['status']);
        $this->assertEquals('O total do slip está dentro do intervalo esperado.', $responseContent['message']);
    }

    public function test_it_should_generate_alert_when_total_is_below_range(): void
    {
        $payload = ['amount' => 400000];

        $this->client->request(
            'POST',
            '/api/v1/slips/check-total',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('alert_generated', $responseContent['status']);
        $this->assertIsArray($responseContent['message']);
        $this->assertEquals('Alerta: Total de Slip Baixo', $responseContent['message']['title']);
    }

    public function test_it_should_generate_alert_when_total_is_above_range(): void
    {
        $payload = ['amount' => 1200000];

        $this->client->request(
            'POST',
            '/api/v1/slips/check-total',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('alert_generated', $responseContent['status']);
        $this->assertIsArray($responseContent['message']);
        $this->assertEquals('Alerta: Total de Slip Elevado', $responseContent['message']['title']);
    }

    public function test_it_should_return_bad_request_for_invalid_payload(): void
    {
        $payload = ['invalid_key' => 123];

        $this->client->request(
            'POST',
            '/api/v1/slips/check-total',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
