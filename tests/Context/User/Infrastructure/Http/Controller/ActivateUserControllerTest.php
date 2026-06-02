<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Domain\User;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Context\User\Domain\UserMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;

final class ActivateUserControllerTest extends ApiTestCase
{
    protected ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');
    }

    public function testItShouldActivateAUserWithAValidToken(): void
    {
        $user = UserMother::createRandom();
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            sprintf('/api/v1/users/activate/%s/%s', $user->getId(), $user->getConfirmationToken()),
        );

        $this->assertResponseIsSuccessful();
        $activatedUser = $this->entityManager->find(User::class, $user->getId());
        $this->assertTrue($activatedUser->isActive());
    }

    public function testItShouldReturnNotFoundForANonExistentUser(): void
    {
        $this->client->request(
            'GET',
            sprintf('/api/v1/users/activate/%s/some-token', Uuid::random()->value()),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testItShouldReturnBadRequestForAnInvalidToken(): void
    {
        $user = UserMother::createRandom();
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            sprintf('/api/v1/users/activate/%s/this-is-a-wrong-token', $user->getId()),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
