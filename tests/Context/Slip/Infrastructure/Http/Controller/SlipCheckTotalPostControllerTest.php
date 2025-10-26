<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Infrastructure\Http\Controller;

use App\Shared\Application\TextGeneratorInterface;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;

final class SlipCheckTotalPostControllerTest extends ApiTestCase
{
    private MockObject|TextGeneratorInterface $textGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();

        // Create mock for the text generator
        $this->textGenerator = $this->createMock(TextGeneratorInterface::class);

        // Replace the service in the container
        static::getContainer()->set(TextGeneratorInterface::class, $this->textGenerator);
    }

    public function test_it_should_return_ok_when_total_is_within_range(): void
    {
        // Expect the text generator NOT to be called
        $this->textGenerator->expects($this->never())->method('generate');

        $payload = ['amount' => 750000]; // 7.500,00 (within 5.000 and 10.000)

        $this->client->request(
            'POST',
            '/api/v1/slips/check-total',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSame('ok', $responseData['status']);
        $this->assertSame('O total de gastos do slip está dentro do intervalo esperado.', $responseData['message']);
        $this->assertSame(750000, $responseData['amount']);
    }

    public function test_it_should_generate_alert_when_total_is_too_low(): void
    {
        $generatedMessage = 'Alerta: O total é muito baixo.';

        $this->textGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($this->stringContains('muito baixo'))
            ->willReturn($generatedMessage);

        $payload = ['amount' => 400000]; // Below the minimum of 500.000

        $this->client->request(
            'POST',
            '/api/v1/slips/check-total',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSame('alert_generated', $responseData['status']);
        $this->assertSame($generatedMessage, $responseData['message']);
        $this->assertSame(400000, $responseData['amount']);
    }

    public function test_it_should_generate_alert_when_total_is_too_high(): void
    {
        $generatedMessage = 'Alerta: O total é muito alto.';

        $this->textGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($this->stringContains('muito alto'))
            ->willReturn($generatedMessage);

        $payload = ['amount' => 1200000]; // Above the maximum of 1.000.000

        $this->client->request(
            'POST',
            '/api/v1/slips/check-total',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSame('alert_generated', $responseData['status']);
        $this->assertSame($generatedMessage, $responseData['message']);
        $this->assertSame(1200000, $responseData['amount']);
    }

    public function test_it_should_return_bad_request_if_amount_is_missing(): void
    {
        $this->textGenerator->expects($this->never())->method('generate');

        $this->client->request(
            'POST',
            '/api/v1/slips/check-total',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
