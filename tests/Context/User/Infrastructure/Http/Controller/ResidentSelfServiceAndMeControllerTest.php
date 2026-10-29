<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Domain\User;
use App\Tests\Context\User\Domain\ValueObject\EmailMother;
use App\Tests\Context\User\Domain\ValueObject\PasswordMother;
use App\Tests\Context\User\Domain\ValueObject\UserIdMother;
use App\Tests\Context\User\Domain\ValueObject\UserNameMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use function json_decode;
use function json_encode;
use function sprintf;

final class ResidentSelfServiceAndMeControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createAuthenticatedClient('resident@example.com', 'resident-pass-123', asSyndic: false);
    }

    public function testMeEndpointReturnsAuthenticatedUserProfile(): void
    {
        $this->client->request('GET', '/api/v1/users/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('roles', $data);
        $this->assertArrayHasKey('residentUnitId', $data);
    }

    public function testResidentCanUpdateOwnProfileAndChangeOwnPassword(): void
    {
        $user = $this->getAuthenticatedUser();
        $ownId = (string) $user->id();

        // Update own profile
        $this->client->request(
            'PUT',
            sprintf('/api/v1/users/%s', $ownId),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Updated Name',
                'lastName' => 'Updated Last',
                'gender' => 'M',
                'phoneNumber' => '+555123456',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Change own password
        $this->client->request(
            'PATCH',
            '/api/v1/users/change-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'oldPassword' => 'resident-pass-123',
                'newPassword' => 'new-resident-pass-456',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testResidentCannotUpdateAnotherUsersProfileReturns403(): void
    {
        // Create another user
        $container = self::getContainer();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $otherUser = User::create(
            UserIdMother::create(),
            UserNameMother::create('Other User'),
            EmailMother::create('other@example.com'),
            PasswordMother::create('other-pass-123'),
            $passwordHasher,
        );
        $otherUser->activate();

        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();

        $otherId = (string) $otherUser->id();

        // Try to update another user's profile
        $this->client->request(
            'PUT',
            sprintf('/api/v1/users/%s', $otherId),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Hacked Name',
                'lastName' => 'Hacked Last',
                'gender' => 'F',
                'phoneNumber' => '+555999888',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
