<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Domain\User;
use App\Context\User\Domain\ValueObject\Email;
use App\Context\User\Domain\ValueObject\UserId;
use App\Context\User\Domain\ValueObject\UserName;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Context\User\Domain\UserMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use function sprintf;

final class ActivateUserControllerTest extends ApiTestCase
{
    protected ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');
    }

    public function testItShouldRedirectToSignInAfterActivatingRegisteredUser(): void
    {
        $user = UserMother::createRandom();
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            sprintf('/api/v1/users/activate/%s/%s', $user->getId(), $user->getConfirmationToken()),
        );

        $this->assertResponseRedirects();
        $this->assertStringEndsWith('/signin', $this->client->getResponse()->headers->get('Location'));

        $activatedUser = $this->entityManager->find(User::class, $user->getId());
        $this->assertTrue($activatedUser->isActive());
    }

    public function testItShouldRedirectToSetPasswordAfterActivatingInvitedUser(): void
    {
        $residentUnit = $this->createResidentUnitForInvite();

        $user = User::invite(
            UserId::fromString($userId = (string) Uuid::random()),
            UserName::fromString('Convidado'),
            Email::fromString('convidado@example.com'),
            $residentUnit,
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            sprintf('/api/v1/users/activate/%s/%s', $userId, $user->getConfirmationToken()),
        );

        $this->assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/set-password/' . $userId . '/', $location);

        $activatedUser = $this->entityManager->find(User::class, $userId);
        $this->assertTrue($activatedUser->isActive());
        $this->assertNotNull($activatedUser->getPasswordResetToken());
    }

    public function testItShouldRedirectToSignInWithErrorForANonExistentUser(): void
    {
        $this->client->request(
            'GET',
            sprintf('/api/v1/users/activate/%s/some-token', Uuid::random()->value()),
        );

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/signin?error=activation_failed', $this->client->getResponse()->headers->get('Location'));
    }

    public function testItShouldRedirectToSignInWithErrorForAnInvalidToken(): void
    {
        $user = UserMother::createRandom();
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            sprintf('/api/v1/users/activate/%s/this-is-a-wrong-token', $user->getId()),
        );

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/signin?error=activation_failed', $this->client->getResponse()->headers->get('Location'));
    }

    private function createResidentUnitForInvite(): \App\Context\ResidentUnit\Domain\ResidentUnit
    {
        $unit = \App\Context\ResidentUnit\Domain\ResidentUnit::create(
            new \App\Context\ResidentUnit\Domain\ResidentUnitId((string) Uuid::random()),
            new \App\Context\ResidentUnit\Domain\ResidentUnitVO('101'),
            new \App\Context\ResidentUnit\Domain\ResidentUnitIdealFraction(0.1),
        );
        $this->entityManager->persist($unit);
        $this->entityManager->flush();

        return $unit;
    }
}
