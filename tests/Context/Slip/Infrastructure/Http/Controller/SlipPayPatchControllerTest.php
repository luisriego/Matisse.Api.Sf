<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Domain\Slip;
use App\Tests\Context\Slip\Domain\SlipMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

final class SlipPayPatchControllerTest extends ApiTestCase
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

    /** @test
     * @throws \DateMalformedStringException
     */
    public function test_it_should_pay_a_submitted_slip_and_return_accepted(): void
    {
        // Arrange: Create a slip and transition it to 'submitted'
        $slip = SlipMother::create(); // Initial status is 'pending'
        $this->entityManager->persist($slip->residentUnit());
        $this->entityManager->persist($slip);
        $this->entityManager->flush();

        /** @var WorkflowInterface $slipStateMachine */
        $slipStateMachine = self::getContainer()->get('state_machine.slip');
        $slipStateMachine->apply($slip, 'send'); // Transition: pending -> submitted
        $this->entityManager->flush();

        // Act: Call the pay endpoint
        $this->client->request(
            'PATCH',
            sprintf('/api/v1/slips/pay/%s', $slip->id())
        );

        // Assert
        self::assertSame(Response::HTTP_ACCEPTED, $this->client->getResponse()->getStatusCode());

        $this->entityManager->clear();
        $updatedSlip = $this->entityManager->find(Slip::class, $slip->id());
        self::assertSame('paid', $updatedSlip->getStatus());
    }

    /** @test */
    public function test_it_should_return_not_found_for_a_non_existent_slip(): void
    {
        // Arrange
        $nonExistentId = Uuid::v4()->toRfc4122();

        // Act
        $this->client->request(
            'PATCH',
            sprintf('/api/v1/slips/pay/%s', $nonExistentId)
        );

        // Assert
        self::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /** @test
     * @throws \DateMalformedStringException
     */
    public function test_it_should_return_conflict_when_transition_is_not_valid(): void
    {
        // Arrange: Create a slip with 'pending' status (the default)
        $slip = SlipMother::create();
        $this->entityManager->persist($slip->residentUnit());
        $this->entityManager->persist($slip);
        $this->entityManager->flush();

        // Act: Try to pay a 'pending' slip, which is an invalid transition
        $this->client->request(
            'PATCH',
            sprintf('/api/v1/slips/pay/%s', $slip->id())
        );

        // Assert
        self::assertSame(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());

        $this->entityManager->clear();
        $notUpdatedSlip = $this->entityManager->find(Slip::class, $slip->id());
        self::assertSame('pending', $notUpdatedSlip->getStatus()); // Status should not have changed
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
