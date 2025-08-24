<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Domain\Slip;
use App\Tests\Context\Slip\Domain\SlipMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Response;

final class SlipsBulkSendPostControllerTest extends ApiTestCase
{
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        // Re-creamos el esquema en cada test para asegurar un estado limpio
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    /** @test */
    public function test_it_should_send_multiple_slips_and_return_accepted(): void
    {
        // ... (test existente)
        $slip1 = SlipMother::create();
        $slip2 = SlipMother::create();
        $this->entityManager->persist($slip1->residentUnit());
        $this->entityManager->persist($slip2->residentUnit());
        $this->entityManager->persist($slip1);
        $this->entityManager->persist($slip2);
        $this->entityManager->flush();
        $slipIds = [$slip1->id(), $slip2->id()];

        $this->client->request(
            'POST',
            '/api/v1/slips/bulk-send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['slip_ids' => $slipIds])
        );

        self::assertSame(Response::HTTP_ACCEPTED, $this->client->getResponse()->getStatusCode());
        $this->entityManager->clear();
        $updatedSlip1 = $this->entityManager->find(Slip::class, $slip1->id());
        $updatedSlip2 = $this->entityManager->find(Slip::class, $slip2->id());
        self::assertSame('submitted', $updatedSlip1->getStatus());
        self::assertSame('submitted', $updatedSlip2->getStatus());
    }

    /** @test */
    public function test_it_should_return_bad_request_for_invalid_payload(): void
    {
        // ... (test existente)
        $this->client->request(
            'POST',
            '/api/v1/slips/bulk-send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['invalid_field' => []])
        );

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /** @test */
    public function test_it_should_only_send_valid_slips_in_a_batch(): void
    {
        // ... (test existente)
        $pendingSlip = SlipMother::create();
        $paidSlip = SlipMother::create();
        $paidSlip->setStatus('paid');
        $this->entityManager->persist($pendingSlip->residentUnit());
        $this->entityManager->persist($paidSlip->residentUnit());
        $this->entityManager->persist($pendingSlip);
        $this->entityManager->persist($paidSlip);
        $this->entityManager->flush();
        $slipIds = [$pendingSlip->id(), $paidSlip->id()];

        $this->client->request(
            'POST',
            '/api/v1/slips/bulk-send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['slip_ids' => $slipIds])
        );

        self::assertSame(Response::HTTP_ACCEPTED, $this->client->getResponse()->getStatusCode());
        $this->entityManager->clear();
        $updatedPendingSlip = $this->entityManager->find(Slip::class, $pendingSlip->id());
        $updatedPaidSlip = $this->entityManager->find(Slip::class, $paidSlip->id());
        self::assertSame('submitted', $updatedPendingSlip->getStatus());
        self::assertSame('paid', $updatedPaidSlip->getStatus());
    }

    // --- NUEVOS TESTS PARA CASOS EXTREMOS ---

    /** @test */
    public function test_it_should_handle_an_empty_array_of_ids_gracefully(): void
    {
        // Act: Llamamos al endpoint con un array vacío
        $this->client->request(
            'POST',
            '/api/v1/slips/bulk-send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['slip_ids' => []])
        );

        // Assert: La aplicación debe aceptar la petición y no hacer nada
        self::assertSame(Response::HTTP_ACCEPTED, $this->client->getResponse()->getStatusCode());
    }

    /** @test */
    public function test_it_should_ignore_non_existent_ids(): void
    {
        // Arrange: Creamos un único recibo válido
        $existingSlip = SlipMother::create();
        $this->entityManager->persist($existingSlip->residentUnit());
        $this->entityManager->persist($existingSlip);
        $this->entityManager->flush();

        $slipIds = [$existingSlip->id(), 'a1b2c3d4-e5f6-7890-1234-567890abcdef'];

        // Act: Llamamos al endpoint con una mezcla de IDs válidos e inválidos
        $this->client->request(
            'POST',
            '/api/v1/slips/bulk-send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['slip_ids' => $slipIds])
        );

        // Assert: La petición se acepta y el recibo válido se procesa
        self::assertSame(Response::HTTP_ACCEPTED, $this->client->getResponse()->getStatusCode());

        $this->entityManager->clear();
        $updatedSlip = $this->entityManager->find(Slip::class, $existingSlip->id());
        self::assertSame('submitted', $updatedSlip->getStatus());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
