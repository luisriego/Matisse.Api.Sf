<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Infrastructure\Http\Controller;

use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Context\Slip\Domain\SlipMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class GetSlipDetailsControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_return_slip_details(): void
    {
        // 1. Arrange: Create a slip for a resident unit
        $residentUnit = ResidentUnitMother::create();
        $this->entityManager->persist($residentUnit);

        $slip = SlipMother::create(null, null, $residentUnit);
        $this->entityManager->persist($slip);
        
        $this->entityManager->flush();

        // 2. Act: Make the API request
        $this->client->request('GET', '/api/v1/slips/' . $slip->id());

        // 3. Assert: Check the response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        // Assert main slip details
        $this->assertSame($slip->id(), $data['id']);
        $this->assertSame($slip->amount(), $data['amount']);
        $this->assertSame($slip->getStatus(), $data['status']);
        $this->assertArrayHasKey('description', $data);
        $this->assertArrayHasKey('createdAt', $data);

        // Assert nested resident unit details
        $this->assertArrayHasKey('residentUnit', $data);
        $this->assertSame($residentUnit->id(), $data['residentUnit']['id']);
        $this->assertSame($residentUnit->unit(), $data['residentUnit']['unit']);

        // Assert that incomes and expenses are NOT present
        $this->assertArrayNotHasKey('incomes', $data);
        $this->assertArrayNotHasKey('expenses', $data);
    }
}
