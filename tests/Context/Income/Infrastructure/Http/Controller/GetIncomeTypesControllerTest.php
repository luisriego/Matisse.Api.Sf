<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Infrastructure\Http\Controller;

use App\Context\Income\Domain\ValueObject\IncomeId;
use App\Context\Income\Infrastructure\Http\Controller\GetIncomeTypesController;
use App\Tests\Context\Income\Domain\IncomeTypeMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \App\Context\Income\Infrastructure\Http\Controller\GetIncomeTypesController
 */
final class GetIncomeTypesControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function test_it_should_return_all_income_types(): void
    {
        // 1. Create a couple of income types to ensure the list is not empty
        $type1 = IncomeTypeMother::create(id: new IncomeId(UuidMother::create()));
        $type2 = IncomeTypeMother::create(id: new IncomeId(UuidMother::create()));

        $this->entityManager->persist($type1);
        $this->entityManager->persist($type2);
        $this->entityManager->flush();

        // 2. Send the GET request
        $this->client->request('GET', '/api/v1/income-types');

        // 3. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        // 4. Assert the content of the response
        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        // Check that the IDs of the created types are in the response
        $responseIds = array_column($data, 'id');
        $this->assertContains($type1->id(), $responseIds);
        $this->assertContains($type2->id(), $responseIds);
    }

    public function test_it_maps_exceptions_correctly(): void
    {
        $controller = $this->getContainer()->get(GetIncomeTypesController::class);
        $exceptions = $controller->exceptions();

        $this->assertIsArray($exceptions);
        $this->assertEmpty($exceptions);
    }
}
