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
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createAuthenticatedClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');
    }

    /** @test */
    public function test_it_should_send_multiple_slips_and_return_accepted(): void
    {
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

        $this->assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
    }

    /** @test */
    public function test_it_should_only_send_valid_slips_in_a_batch(): void
    {
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

        $this->assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
    }
}
