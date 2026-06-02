<?php

declare(strict_types=1);

namespace App\Tests\Context\Forecast\Infrastructure\Http\Controller;

use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;

final class ForecastGetControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createAuthenticatedClient();
    }

    public function testItReturnsForecastPayloadForValidTargetMonth(): void
    {
        $unit = ResidentUnitMother::create();
        $this->entityManager->persist($unit);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/api/v1/forecast/2024-09?reconciliationMonth=2024-08',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('data', $data);
        $this->assertSame('2024-09', $data['data']['targetMonth']);
        $this->assertSame('2024-08', $data['data']['reconciliationMonth']);
        $this->assertTrue($data['data']['isProjectionOnly']);
        $this->assertSame('previsao', $data['data']['documentKind']);
        $this->assertArrayHasKey('expectedExpenseLines', $data['data']);
        $this->assertArrayHasKey('totals', $data['data']);
    }

    public function testItReturnsBadRequestOnInvalidReconciliationMonth(): void
    {
        $unit = ResidentUnitMother::create();
        $this->entityManager->persist($unit);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/api/v1/forecast/2024-09?reconciliationMonth=invalid',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
