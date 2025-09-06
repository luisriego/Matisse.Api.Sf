<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Infrastructure\Http\Controller;

use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Response;

final class SlipGenerationPostControllerTest extends ApiTestCase
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

    /** @test */
    public function test_it_should_generate_slips_and_return_created(): void
    {
        // Arrange
        $payload = [
            'targetMonth' => '2024-08',
            'force' => false,
        ];

        // Act
        $this->client->request(
            'POST',
            '/api/v1/slips/generation',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Assert
        self::assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        // Aquí se podría añadir una aserción para verificar que los slips se han creado en la base de datos.
    }

    /** @test */
    public function it_should_return_bad_request_for_invalid_payload(): void
    {
        // Arrange: 'targetMonth' con formato incorrecto
        $payload = [
            'targetMonth' => '2024/08',
        ];

        // Act
        $this->client->request(
            'POST',
            '/api/v1/slips/generation',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Assert
        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
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
