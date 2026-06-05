<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Domain\Exception\ResidentUnitAlreadyExistsException;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Infrastructure\Http\Controller\ResidentUnitCreateController;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;
use function json_encode;

/**
 * @covers \App\Context\ResidentUnit\Infrastructure\Http\Controller\ResidentUnitCreateController
 */
final class ResidentUnitCreateControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();
    }

    public function testItShouldCreateResidentUnit(): void
    {
        // 1. Define the payload
        $residentUnitId = UuidMother::create();
        $payload = [
            'id' => $residentUnitId,
            'unit' => 'A101',
            'idealFraction' => 0.05,
            'email' => 'residente@example.com',
            'name' => 'João Silva',
        ];

        // 2. Send the PUT request
        $this->client->request(
            'PUT',
            '/api/v1/resident-unit/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
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
        // self::assertEquals($payload['notificationRecipients'], $createdResidentUnit->notificationRecipients()); // Not applicable
    }

    public function testItShouldReturnBadRequestIfIdealFractionIsInvalid(): void
    {
        $residentUnitId = UuidMother::create();
        $payload = [
            'id' => $residentUnitId,
            'unit' => 'A102',
            'idealFraction' => 1.5, // Invalid ideal fraction
            'email' => 'residente@example.com',
        ];

        $this->client->request(
            'PUT',
            '/api/v1/resident-unit/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('A fração ideal deve ser maior ou igual a zero e menor ou igual a um.', $responseContent['message']);
    }

    public function testItShouldReturnConflictIfResidentUnitAlreadyExists(): void
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
            'email' => 'otro@example.com',
        ];

        $this->client->request(
            'PUT',
            '/api/v1/resident-unit/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Resident unit with ID', $responseContent['message']);
        $this->assertStringContainsString('already exists', $responseContent['message']);
    }

    public function testItMapsExceptionsCorrectly(): void
    {
        $controller = $this->getContainer()->get(ResidentUnitCreateController::class);
        $exceptions = $controller->exceptions();

        $this->assertArrayHasKey(InvalidArgumentException::class, $exceptions);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $exceptions[InvalidArgumentException::class]);

        $this->assertArrayHasKey(ResidentUnitAlreadyExistsException::class, $exceptions);
        self::assertEquals(Response::HTTP_CONFLICT, $exceptions[ResidentUnitAlreadyExistsException::class]);
    }
}
