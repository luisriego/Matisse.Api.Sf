<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Infrastructure\Http\Controller\ListActiveResidentUnitsController;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \App\Context\ResidentUnit\Infrastructure\Http\Controller\ListActiveResidentUnitsController
 */
final class ListActiveResidentUnitsControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws \ReflectionException
     */
    public function test_it_should_return_only_active_resident_units(): void
    {
        // 1. Create resident units for the test scenario
        $activeUnit1 = ResidentUnitMother::create();
        $activeUnit2 = ResidentUnitMother::create();
        
        // Create an inactive unit by creating it active and then setting isActive to false
        $inactiveUnit = ResidentUnitMother::create();
        // Access the property directly as there's no public setter for isActive
        $reflection = new \ReflectionProperty($inactiveUnit, 'isActive');
        $reflection->setValue($inactiveUnit, false);

        // 2. Persist all entities
        $this->entityManager->persist($activeUnit1);
        $this->entityManager->persist($activeUnit2);
        $this->entityManager->persist($inactiveUnit);
        $this->entityManager->flush();

        // 3. Send the GET request
        $this->client->request('GET', '/api/v1/resident-unit/actives');

        // 4. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        // 5. Assert the content of the response
        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        $responseIds = array_column($data, 'id');
        $this->assertContains($activeUnit1->id(), $responseIds);
        $this->assertContains($activeUnit2->id(), $responseIds);
        $this->assertNotContains($inactiveUnit->id(), $responseIds);
    }

    public function test_it_maps_exceptions_correctly(): void
    {
        $controller = $this->getContainer()->get(ListActiveResidentUnitsController::class);
        $exceptions = $controller->exceptions();

        $this->assertIsArray($exceptions);
        $this->assertEmpty($exceptions);
    }
}
