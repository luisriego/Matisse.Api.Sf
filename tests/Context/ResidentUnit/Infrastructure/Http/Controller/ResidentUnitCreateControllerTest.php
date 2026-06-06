<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\User\Domain\User;
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

    public function testItShouldUpdateResidentUnitWhenAlreadyExists(): void
    {
        $existingResidentUnit = ResidentUnitMother::create();
        $this->entityManager->persist($existingResidentUnit);
        $this->entityManager->flush();

        $payload = [
            'id' => $existingResidentUnit->id(),
            'unit' => 'Apto. 401',
            'idealFraction' => 0.20,
            'email' => 'residente-updated@example.com',
            'name' => 'Luis',
        ];

        $this->client->request(
            'PUT',
            '/api/v1/resident-unit/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->entityManager->clear();
        /** @var ResidentUnit|null $updatedResidentUnit */
        $updatedResidentUnit = $this->entityManager->find(ResidentUnit::class, $existingResidentUnit->id());

        $this->assertNotNull($updatedResidentUnit);
        self::assertEquals($payload['unit'], $updatedResidentUnit->unit());
        self::assertEquals($payload['idealFraction'], $updatedResidentUnit->idealFraction());

        /** @var User|null $user */
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $payload['email']]);
        $this->assertNotNull($user);
        self::assertEquals('Luis', $user->getName());
        self::assertEquals($existingResidentUnit->id(), $user->getResidentUnit()?->id());
    }

    public function testItMapsExceptionsCorrectly(): void
    {
        $controller = $this->getContainer()->get(ResidentUnitCreateController::class);
        $exceptions = $controller->exceptions();

        $this->assertArrayHasKey(InvalidArgumentException::class, $exceptions);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $exceptions[InvalidArgumentException::class]);

    }
}
