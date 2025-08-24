<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Domain\Slip;
use App\Tests\Context\Slip\Domain\SlipMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final class SlipsBulkSendPostControllerTest extends ApiTestCase
{
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');
    }

    /** @test
     * @throws \DateMalformedStringException
     */
    public function test_it_should_send_multiple_slips_and_return_accepted(): void
    {
        $slip1 = SlipMother::create();
        $slip2 = SlipMother::create();

        $this->entityManager->persist($slip1->residentUnit()); // <-- AÑADIDO
        $this->entityManager->persist($slip2->residentUnit()); // <-- AÑADIDO

        $this->entityManager->persist($slip1);
        $this->entityManager->persist($slip2);
        $this->entityManager->flush();

        $slipIds = [$slip1->id(), $slip2->id()];

        // Act: Call the bulk send endpoint
        $this->client->request(
            'POST',
            '/api/v1/slips/bulk-send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['slip_ids' => $slipIds])
        );

        // Assert: Check the response and the database state
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
        // Act: Call the endpoint with a missing 'slip_ids' field
        $this->client->request(
            'POST',
            '/api/v1/slips/bulk-send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['invalid_field' => []])
        );

        // Assert: Check for a 400 response
        self::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    /** @test
     * @throws \DateMalformedStringException
     */
    public function test_it_should_only_send_valid_slips_in_a_batch(): void
    {
        // Arrange: Create one pending and one already paid slip
        $pendingSlip = SlipMother::create();
        $paidSlip = SlipMother::create();
        $paidSlip->setStatus('paid'); // Manually set status

        $this->entityManager->persist($pendingSlip->residentUnit()); // <-- AÑADIDO
        $this->entityManager->persist($paidSlip->residentUnit());     // <-- AÑADIDO

        $this->entityManager->persist($pendingSlip);
        $this->entityManager->persist($paidSlip);
        $this->entityManager->flush();

        $slipIds = [$pendingSlip->id(), $paidSlip->id()];

        // Act: Call the bulk send endpoint
        $this->client->request(
            'POST',
            '/api/v1/slips/bulk-send',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['slip_ids' => $slipIds])
        );

        // Assert: Check the response and the final state of both slips
        self::assertSame(Response::HTTP_ACCEPTED, $this->client->getResponse()->getStatusCode());

        $this->entityManager->clear();
        $updatedPendingSlip = $this->entityManager->find(Slip::class, $pendingSlip->id());
        $updatedPaidSlip = $this->entityManager->find(Slip::class, $paidSlip->id());

        self::assertSame('submitted', $updatedPendingSlip->getStatus()); // This one changed
        self::assertSame('paid', $updatedPaidSlip->getStatus());      // This one did NOT change
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
