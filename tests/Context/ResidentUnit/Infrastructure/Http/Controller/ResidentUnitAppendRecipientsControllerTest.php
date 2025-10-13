<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Tools\SchemaTool; // <-- Importante
use Symfony\Component\HttpFoundation\Response;

final class ResidentUnitAppendRecipientsControllerTest extends ApiTestCase
{
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function test_it_should_append_recipient_and_return_ok(): void
    {
        // Arrange
        $residentUnit = ResidentUnitMother::create();
        $this->entityManager->persist($residentUnit);
        $this->entityManager->flush();

        $payload = [
            'name' => 'Nuevo Residente',
            'email' => 'nuevo@example.com',
        ];

        // Act
        $this->client->request(
            'PATCH',
            sprintf('/api/v1/resident-unit/%s/recipients', $residentUnit->id()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Assert
        self::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $updatedResidentUnit = $this->entityManager->find(ResidentUnit::class, $residentUnit->id());
        $recipients = $updatedResidentUnit->notificationRecipients();

        self::assertCount(1, $recipients);
        self::assertSame('Nuevo Residente', $recipients[0]['name']);
        self::assertSame('nuevo@example.com', $recipients[0]['email']);
    }
    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->entityManager !== null) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }
}