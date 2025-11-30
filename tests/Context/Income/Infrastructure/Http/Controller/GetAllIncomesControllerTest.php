<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Infrastructure\Http\Controller;

use App\Context\Income\Infrastructure\Http\Controller\GetAllIncomesController;
use App\Tests\Context\Income\Domain\IncomeMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \App\Context\Income\Infrastructure\Http\Controller\GetAllIncomesController
 */
final class GetAllIncomesControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_return_all_incomes(): void
    {
        // 1. Create a couple of incomes
        $income1 = IncomeMother::create();
        $income2 = IncomeMother::create();

        $this->entityManager->persist($income1->residentUnit());
        $this->entityManager->persist($income1->incomeType());
        $this->entityManager->persist($income1);

        $this->entityManager->persist($income2->residentUnit());
        $this->entityManager->persist($income2->incomeType());
        $this->entityManager->persist($income2);

        $this->entityManager->flush();

        // 2. Send the GET request
        $this->client->request('GET', '/api/v1/incomes');

        // 3. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        // 4. Assert the content of the response
        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        $responseIds = array_column($data, 'id');
        $this->assertContains($income1->id(), $responseIds);
        $this->assertContains($income2->id(), $responseIds);
    }

    public function test_it_maps_exceptions_correctly(): void
    {
        $controller = $this->getContainer()->get(GetAllIncomesController::class);
        $exceptions = $controller->exceptions();

        $this->assertIsArray($exceptions);
        $this->assertEmpty($exceptions);
    }
}
