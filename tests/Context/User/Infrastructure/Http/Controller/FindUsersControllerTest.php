<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Infrastructure\Http\Controller;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\User\Domain\User;
use App\Context\User\Domain\ValueObject\UserId; // Import UserId
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Context\User\Domain\UserMother;
use App\Tests\Shared\Domain\UuidMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use function json_decode;

final class FindUsersControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient(); // This creates a default authenticated user
    }

    public function testItShouldReturnAllUsersWithResidentUnitData(): void
    {
        // 1. Create and persist a ResidentUnit
        $residentUnit = ResidentUnitMother::create();
        $this->entityManager->persist($residentUnit);

        // 2. Create a User using UserMother::createRandom()
        // We need a real password hasher for User::create
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $user = UserMother::createRandom(
            UserId::fromString(UuidMother::create('b0eebc99-9c0b-4ef8-bb6d-6bb9bd380a12')), // Pass UserId object
            null, // Let mother handle name
            null, // Let mother handle email
            null, // Let mother handle password
            $passwordHasher,
        );
        $user->setResidentUnit($residentUnit); // Manually associate ResidentUnit
        $user->activate(); // Ensure the user is active to be returned by default queries
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // 3. Make the API request
        $this->client->request('GET', '/api/v1/users');

        // 4. Assert the response status
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseContent = $this->client->getResponse()->getContent();
        $data = json_decode($responseContent, true);

        // 5. Assertions on the response structure and data
        $this->assertIsArray($data);
        $this->assertNotEmpty($data, 'The response should not be empty.');

        // Find the created user in the response (there might be other users from createAuthenticatedClient)
        $foundUser = null;

        foreach ($data as $item) {
            if ($item['id'] === $user->id()) {
                $foundUser = $item;
                break;
            }
        }

        $this->assertNotNull($foundUser, 'The created user was not found in the response.');

        // Assert user properties
        $this->assertSame($user->id(), $foundUser['id']);
        $this->assertSame($user->name(), $foundUser['name']);
        $this->assertSame($user->getEmail(), $foundUser['email']);
        $this->assertTrue($foundUser['isActive']);
        $this->assertArrayHasKey('createdAt', $foundUser);
        $this->assertArrayHasKey('updatedAt', $foundUser);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $foundUser['createdAt'], 'User createdAt format is incorrect.');

        if ($foundUser['updatedAt'] !== null) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $foundUser['updatedAt'], 'User updatedAt format is incorrect.');
        }

        // Assert residentUnit properties
        $this->assertArrayHasKey('residentUnit', $foundUser);
        $this->assertIsArray($foundUser['residentUnit']);
        $this->assertArrayHasKey('id', $foundUser['residentUnit']);
        $this->assertArrayHasKey('unit', $foundUser['residentUnit']);
        $this->assertArrayHasKey('idealFraction', $foundUser['residentUnit']);
        $this->assertArrayHasKey('createdAt', $foundUser['residentUnit']);
        $this->assertArrayHasKey('updatedAt', $foundUser['residentUnit']);

        $this->assertSame($residentUnit->id(), $foundUser['residentUnit']['id']);
        $this->assertSame($residentUnit->unit(), $foundUser['residentUnit']['unit']);
        $this->assertSame($residentUnit->idealFraction(), $foundUser['residentUnit']['idealFraction']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $foundUser['residentUnit']['createdAt'], 'ResidentUnit createdAt format is incorrect.');

        if ($foundUser['residentUnit']['updatedAt'] !== null) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $foundUser['residentUnit']['updatedAt'], 'ResidentUnit updatedAt format is incorrect.');
        }
    }
}
