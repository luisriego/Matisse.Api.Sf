<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Domain\User;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use App\Tests\Context\User\Domain\ValueObject\EmailMother;
use App\Tests\Context\User\Domain\ValueObject\PasswordMother;
use App\Tests\Context\User\Domain\ValueObject\UserIdMother;
use App\Tests\Context\User\Domain\ValueObject\UserNameMother;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ChangePasswordControllerTest extends ApiTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?UserPasswordHasherInterface $passwordHasher;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->passwordHasher = $this->client->getContainer()->get('security.password_hasher');

        $this->entityManager->getConnection()->executeStatement('DELETE FROM users');

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function test_it_should_change_password_for_authenticated_user(): void
    {
        // 1. Arrange: Create and activate a user
        $plainPassword = 'old-password-123';
        $user = $this->createAndActivateUser('user@example.com', $plainPassword);
        $token = $this->getAuthToken('user@example.com', $plainPassword);

        $newPassword = 'new-awesome-password-456';

        // 2. Act: Call the change password endpoint
        $this->client->request(
            'PATCH',
            '/api/v1/users/me/password',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'oldPassword' => $plainPassword,
                'newPassword' => $newPassword,
            ])
        );

        // 3. Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // 4. Verify: Try to log in with the new password
        $this->entityManager->clear();
        $newToken = $this->getAuthToken('user@example.com', $newPassword);
        $this->assertNotNull($newToken, 'Should be able to log in with the new password.');
    }

    public function test_it_should_return_bad_request_for_invalid_old_password(): void
    {
        // 1. Arrange
        $plainPassword = 'old-password-123';
        $user = $this->createAndActivateUser('user2@example.com', $plainPassword);
        $token = $this->getAuthToken('user2@example.com', $plainPassword);

        // 2. Act
        $this->client->request(
            'PATCH',
            '/api/v1/users/me/password',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'oldPassword' => 'this-is-a-wrong-password',
                'newPassword' => 'any-new-password',
            ])
        );

        // 3. Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    private function createAndActivateUser(string $email, string $plainPassword): User
    {
        $user = User::create(
            UserIdMother::create(),
            UserNameMother::create(),
            EmailMother::create($email),
            PasswordMother::create($plainPassword),
            $this->passwordHasher
        );
        $user->activate();

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function getAuthToken(string $email, string $plainPassword): ?string
    {
        $this->client->request(
            'POST',
            '/api/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $plainPassword])
        );

        $response = $this->client->getResponse();
        if ($response->getStatusCode() !== Response::HTTP_OK) {
            return null;
        }

        return json_decode($response->getContent(), true)['token'] ?? null;
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
