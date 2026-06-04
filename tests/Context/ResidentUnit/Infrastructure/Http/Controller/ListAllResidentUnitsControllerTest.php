<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitIdealFraction;
use App\Context\ResidentUnit\Infrastructure\Http\Controller\ListAllResidentUnitsController;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Response;

use function array_column;
use function json_decode;
use function json_encode;

/**
 * @covers \App\Context\ResidentUnit\Infrastructure\Http\Controller\ListAllResidentUnitsController
 */
final class ListAllResidentUnitsControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws ReflectionException
     */
    public function testItShouldReturnAllResidentUnitsIncludingInactiveOnes(): void
    {
        $activeUnit = ResidentUnitMother::create(
            idealFraction: new ResidentUnitIdealFraction(0.4),
        );
        $inactiveUnit = ResidentUnitMother::create(
            idealFraction: new ResidentUnitIdealFraction(0.3),
        );
        $reflection = new ReflectionProperty($inactiveUnit, 'isActive');
        $reflection->setValue($inactiveUnit, false);

        $this->entityManager->persist($activeUnit);
        $this->entityManager->persist($inactiveUnit);
        $this->entityManager->flush();

        $this->client->request('GET', '/api/v1/resident-unit/all');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(2, $data);
        $this->assertContains($activeUnit->id(), array_column($data, 'id'));
        $this->assertContains($inactiveUnit->id(), array_column($data, 'id'));
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws ReflectionException
     */
    public function testInactiveUnitsDoNotBlockCreatingNewActiveUnits(): void
    {
        $inactiveUnit = ResidentUnitMother::create(
            idealFraction: new ResidentUnitIdealFraction(0.9),
        );
        $reflection = new ReflectionProperty($inactiveUnit, 'isActive');
        $reflection->setValue($inactiveUnit, false);

        $this->entityManager->persist($inactiveUnit);
        $this->entityManager->flush();

        $newUnitId = UuidMother::create();
        $this->client->request(
            'PUT',
            '/api/v1/resident-unit/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'id' => $newUnitId,
                'unit' => 'Apto 101',
                'idealFraction' => 0.1813176,
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->entityManager->clear();
        $created = $this->entityManager->find(ResidentUnit::class, $newUnitId);
        $this->assertNotNull($created);
        $this->assertTrue($created->isActive());
    }

    public function testItMapsExceptionsCorrectly(): void
    {
        $controller = $this->getContainer()->get(ListAllResidentUnitsController::class);
        $exceptions = $controller->exceptions();

        $this->assertIsArray($exceptions);
        $this->assertEmpty($exceptions);
    }
}
