<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Infrastructure\Http\Controller\GetResidentUnitByIdController;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \App\Context\ResidentUnit\Infrastructure\Http\Controller\GetResidentUnitByIdController
 */
final class GetResidentUnitByIdControllerTest extends ApiTestCase
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
    public function test_it_should_return_resident_unit_when_found(): void
    {
        // 1. Create a resident unit to be found
        $residentUnit = ResidentUnitMother::create();
        $this->entityManager->persist($residentUnit);
        $this->entityManager->flush();

        // 2. Send the GET request
        $this->client->request('GET', '/api/v1/resident-unit/' . $residentUnit->id());

        // 3. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        $this->assertIsArray($data);
        $this->assertEquals($residentUnit->id(), $data['id']);
        $this->assertEquals($residentUnit->unit(), $data['unit']); // Corrected from name() to unit()
    }

    public function test_it_should_return_not_found_when_resident_unit_does_not_exist(): void
    {
        // 1. Generate a random non-existent ID
        $nonExistentId = UuidMother::create();

        // 2. Send the GET request
        $this->client->request('GET', '/api/v1/resident-unit/' . $nonExistentId);

        // 3. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_it_maps_exceptions_correctly(): void
    {
        $controller = $this->getContainer()->get(GetResidentUnitByIdController::class);
        $exceptions = $controller->exceptions();

        $this->assertArrayHasKey(ResourceNotFoundException::class, $exceptions);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $exceptions[ResourceNotFoundException::class]);
    }
}
