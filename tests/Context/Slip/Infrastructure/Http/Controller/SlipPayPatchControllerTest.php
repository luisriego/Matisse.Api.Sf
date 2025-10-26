<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Infrastructure\Http\Controller;

use App\Context\Slip\Domain\Slip;
use App\Tests\Context\Slip\Domain\SlipMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Workflow\WorkflowInterface;

final class SlipPayPatchControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createAuthenticatedClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');
    }

    /** @test */
    public function test_it_should_pay_a_submitted_slip_and_return_accepted(): void
    {
        $slip = SlipMother::create();
        $this->entityManager->persist($slip->residentUnit());
        $this->entityManager->persist($slip);
        $this->entityManager->flush();

        $slipStateMachine = $this->client->getContainer()->get('state_machine.slip');
        $slipStateMachine->apply($slip, 'send');
        $this->entityManager->flush();

        $this->client->request(
            'PATCH',
            sprintf('/api/v1/slips/pay/%s', $slip->id())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
    }
}
