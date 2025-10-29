<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Domain\User;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use App\Tests\Context\User\Domain\ValueObject\EmailMother;
use App\Tests\Context\User\Domain\ValueObject\PasswordMother;
use App\Tests\Context\User\Domain\ValueObject\UserIdMother;
use App\Tests\Context\User\Domain\ValueObject\UserNameMother;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LoginControllerTest extends ApiTestCase
{
    private ?UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function test_it_should_return_a_jwt_token_for_valid_credentials(): void
    {
        $plainPassword = 'my-strong-password-123';
        $userEmail = 'test@example.com';

        $user = User::create(
            UserIdMother::create(),
            UserNameMother::create(),
            EmailMother::create($userEmail),
            PasswordMother::create($plainPassword),
            $this->passwordHasher
        );
        $user->activate();

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request(
            'POST',
            '/api/v1/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $userEmail,
                'password' => $plainPassword,
            ])
        );

        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('token', $responseData);
//        $this->assertArrayHasKey('user', $responseData);
//        $this->assertSame($userEmail, $responseData['user']);
    }

    public function test_it_should_deny_login_for_inactive_user(): void
    {
        $plainPassword = 'my-strong-password-123';
        $userEmail = 'inactive@example.com';

        $user = User::create(
            UserIdMother::create(),
            UserNameMother::create(),
            EmailMother::create($userEmail),
            PasswordMother::create($plainPassword),
            $this->passwordHasher
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request(
            'POST',
            '/api/v1/login_check',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $userEmail,
                'password' => $plainPassword,
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
