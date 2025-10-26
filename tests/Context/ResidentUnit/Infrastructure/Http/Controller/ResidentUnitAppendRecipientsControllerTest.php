<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ResidentUnitAppendRecipientsControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createAuthenticatedClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');
    }

    public function test_it_should_append_recipient_and_return_ok(): void
    {
        $residentUnit = ResidentUnitMother::create();
        $this->entityManager->persist($residentUnit);
        $this->entityManager->flush();

        $payload = [
            'name' => 'Nuevo Residente',
            'email' => 'nuevo@example.com',
        ];

        $this->client->request(
            'PATCH',
            sprintf('/api/v1/resident-unit/%s/recipients', $residentUnit->id()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
