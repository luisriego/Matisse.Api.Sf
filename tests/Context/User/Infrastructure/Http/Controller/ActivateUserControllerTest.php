<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Domain\User;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use App\Tests\Context\User\Domain\UserMother;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Response;

final class ActivateUserControllerTest extends ApiTestCase
{
    protected ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function test_it_should_activate_a_user_with_a_valid_token(): void
    {
        $user = UserMother::createRandom();
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            sprintf('/api/v1/users/activate/%s/%s', $user->getId(), $user->getConfirmationToken())
        );

        $this->assertResponseIsSuccessful();
        $activatedUser = $this->entityManager->find(User::class, $user->getId());
        $this->assertTrue($activatedUser->isActive());
    }

    public function test_it_should_return_not_found_for_a_non_existent_user(): void
    {
        $this->client->request(
            'GET',
            sprintf('/api/v1/users/activate/%s/some-token', Uuid::random()->value())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function test_it_should_return_bad_request_for_an_invalid_token(): void
    {
        $user = UserMother::createRandom();
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            sprintf('/api/v1/users/activate/%s/this-is-a-wrong-token', $user->getId())
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
