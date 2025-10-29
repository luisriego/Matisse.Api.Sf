<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Domain\Slip;
use App\Tests\Context\Slip\Domain\SlipMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Workflow\WorkflowInterface;

final class SlipSendPatchControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    /** @test
     * @throws \DateMalformedStringException
     */
    public function test_it_should_send_a_pending_slip_and_return_accepted(): void
    {
        // Arrange: Create a slip with the initial 'pending' status
        $slip = SlipMother::create();
        $this->entityManager->persist($slip->residentUnit());
        $this->entityManager->persist($slip);
        $this->entityManager->flush();

        // Act: Call the send endpoint
        $this->client->request(
            'PATCH',
            sprintf('/api/v1/slips/send/%s', $slip->id())
        );

        // Assert
        self::assertSame(Response::HTTP_ACCEPTED, $this->client->getResponse()->getStatusCode());

        $this->entityManager->clear();
        $updatedSlip = $this->entityManager->find(Slip::class, $slip->id());
        self::assertSame('submitted', $updatedSlip->getStatus());
    }
}
