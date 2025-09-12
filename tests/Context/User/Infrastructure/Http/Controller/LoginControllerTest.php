<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Domain\User;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use App\Tests\Context\User\Domain\ValueObject\EmailMother;
use App\Tests\Context\User\Domain\ValueObject\PasswordMother;
use App\Tests\Context\User\Domain\ValueObject\UserIdMother;
use App\Tests\Context\User\Domain\ValueObject\UserNameMother;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LoginControllerTest extends ApiTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->passwordHasher = $this->client->getContainer()->get('security.password_hasher');

        // Clean up the users table before recreating the schema
        $this->entityManager->getConnection()->executeStatement('DELETE FROM users');

        // Recreate the database schema to ensure a clean state
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function test_it_should_return_a_jwt_token_for_valid_credentials(): void
    {
        // 1. Arrange: Create a user in the database
        $plainPassword = 'my-strong-password-123';
        
        // Use User::create() to instantiate the user
        $user = User::create(
            UserIdMother::create(),
            UserNameMother::create(),
            EmailMother::create('test@example.com'),
            PasswordMother::create($plainPassword),
            $this->passwordHasher,
            18
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // 2. Act: Make the login request
        $this->client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test@example.com',
                'password' => $plainPassword,
            ])
        );

        // 3. Assert
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('token', $responseData, 'Response should contain a JWT token.');
        $this->assertArrayHasKey('user', $responseData, 'Response should contain user data.');
        $this->assertSame('test@example.com', $responseData['user']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->entityManager !== null) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
        $this->passwordHasher = null;
    }
}
