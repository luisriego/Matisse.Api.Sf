<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Domain\Exception\ResidentUnitAlreadyExistsException;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Infrastructure\Http\Controller\ResidentUnitCreateWithRecipientsController;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @covers \App\Context\ResidentUnit\Infrastructure\Http\Controller\ResidentUnitCreateWithRecipientsController
 */
final class ResidentUnitCreateWithRecipientsControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function test_it_should_create_resident_unit_with_recipients(): void
    {
        // 1. Define the payload
        $residentUnitId = UuidMother::create();
        $payload = [
            'id' => $residentUnitId,
            'unit' => 'B201',
            'idealFraction' => 0.10,
            'notificationRecipients' => [
                ['name' => 'Jane Doe', 'email' => 'jane.doe@example.com'],
                ['name' => 'John Smith', 'email' => 'john.smith@example.com'],
            ],
        ];

        // 2. Send the PUT request
        $this->client->request(
            'PUT',
            '/api/v1/resident-unit/create-with-recipients',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // 3. Assert the response
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // 4. Assert that the resident unit was created in the database
        $this->entityManager->clear();
        /** @var ResidentUnit|null $createdResidentUnit */
        $createdResidentUnit = $this->entityManager->find(ResidentUnit::class, $residentUnitId);

        $this->assertNotNull($createdResidentUnit);
        self::assertEquals($payload['unit'], $createdResidentUnit->unit());
        self::assertEquals($payload['idealFraction'], $createdResidentUnit->idealFraction());
        self::assertEquals($payload['notificationRecipients'], $createdResidentUnit->notificationRecipients());
    }

    public function test_it_should_return_bad_request_if_ideal_fraction_is_invalid(): void
    {
        $residentUnitId = UuidMother::create();
        $payload = [
            'id' => $residentUnitId,
            'unit' => 'B202',
            'idealFraction' => 1.1, // Invalid ideal fraction
            'notificationRecipients' => [],
        ];

        $this->client->request(
            'PUT',
            '/api/v1/resident-unit/create-with-recipients',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('A fração ideal deve ser maior ou igual a zero e menor ou igual a um.', $responseContent['message']);
    }

    public function test_it_should_return_conflict_if_resident_unit_already_exists(): void
    {
        // 1. Create a resident unit first
        $existingResidentUnit = ResidentUnitMother::create();
        $this->entityManager->persist($existingResidentUnit);
        $this->entityManager->flush();

        // 2. Try to create another one with the same ID
        $payload = [
            'id' => $existingResidentUnit->id(),
            'unit' => 'C301',
            'idealFraction' => 0.20,
            'notificationRecipients' => [],
        ];

        $this->client->request(
            'PUT',
            '/api/v1/resident-unit/create-with-recipients',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Resident unit with ID', $responseContent['message']);
        $this->assertStringContainsString('already exists', $responseContent['message']);
    }

    public function test_it_maps_exceptions_correctly(): void
    {
        $controller = $this->getContainer()->get(ResidentUnitCreateWithRecipientsController::class);
        $exceptions = $controller->exceptions();

        $this->assertArrayHasKey(InvalidArgumentException::class, $exceptions);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $exceptions[InvalidArgumentException::class]);

        $this->assertArrayHasKey(ResidentUnitAlreadyExistsException::class, $exceptions);
        self::assertEquals(Response::HTTP_CONFLICT, $exceptions[ResidentUnitAlreadyExistsException::class]);
    }
}
