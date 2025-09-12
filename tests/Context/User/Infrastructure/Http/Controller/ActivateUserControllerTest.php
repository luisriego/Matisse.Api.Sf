<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Domain\User;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use App\Tests\Context\User\Domain\UserMother;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Response;

final class ActivateUserControllerTest extends ApiTestCase
{
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        // Clean up the users table before recreating the schema
        $this->entityManager->getConnection()->executeStatement('DELETE FROM users');

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function test_it_should_activate_a_user_with_a_valid_token(): void
    {
        // 1. Arrange: Create an inactive user in the database
        $user = UserMother::createRandom();
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $userId = $user->getId();
        $token = $user->getConfirmationToken();

        // 2. Act: Call the activation endpoint
        $this->client->request(
            'GET',
            sprintf('/api/v1/users/activate/%s/%s', $userId, $token)
        );

        // 3. Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Clear the entity manager's identity map to ensure we get a fresh object from the DB
        $this->entityManager->clear();

        // Verify the user is now active in the database
        $activatedUser = $this->entityManager->find(User::class, $userId);
        $this->assertNotNull($activatedUser, 'User should exist in the database.');
        $this->assertTrue($activatedUser->isActive());
        $this->assertNull($activatedUser->getConfirmationToken());
    }

    public function test_it_should_return_not_found_for_a_non_existent_user(): void
    {
        // 1. Act
        $this->client->request(
            'GET',
            '/api/v1/users/activate/non-existent-id/some-token'
        );

        // 2. Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_it_should_return_bad_request_for_an_invalid_token(): void
    {
        // 1. Arrange
        $user = UserMother::createRandom();
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // 2. Act
        $this->client->request(
            'GET',
            sprintf('/api/v1/users/activate/%s/this-is-a-wrong-token', $user->getId())
        );

        // 3. Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
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
