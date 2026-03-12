<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\PhpUnit;

use App\Context\User\Domain\User;
use App\Kernel;
use App\Tests\Context\User\Domain\ValueObject\EmailMother;
use App\Tests\Context\User\Domain\ValueObject\PasswordMother;
use App\Tests\Context\User\Domain\ValueObject\UserIdMother;
use App\Tests\Context\User\Domain\ValueObject\UserNameMother;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiTestCase extends WebTestCase
{
    protected ?KernelBrowser $client = null;
    protected ?EntityManagerInterface $entityManager = null;
    protected ?User $authenticatedUser = null;

    /**
     * Truncate all tables once per test class.
     * DAMA doctrine-test-bundle then wraps each individual test in a transaction
     * with automatic rollback, so no per-test reset is needed.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::bootKernel(['environment' => 'test']);
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $connection = $entityManager->getConnection();

        $connection->executeStatement('SET session_replication_role = replica');
        foreach ($entityManager->getMetadataFactory()->getAllMetadata() as $meta) {
            $connection->executeStatement(sprintf('TRUNCATE TABLE %s CASCADE', $meta->getTableName()));
        }
        $connection->executeStatement('SET session_replication_role = DEFAULT');

        static::ensureKernelShutdown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['APP_ENV'] = 'test';

        if (method_exists(Dotenv::class, 'bootEnv')) {
            (new Dotenv())->bootEnv(dirname(__DIR__, 4) . '/.env');
        }

        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function createAuthenticatedClient(string $email = 'test@example.com', string $password = 'password'): KernelBrowser
    {
        $container = static::getContainer();

        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');

        $user = User::create(
            UserIdMother::create(),
            UserNameMother::create('Test User'),
            EmailMother::create($email),
            PasswordMother::create($password),
            $passwordHasher
        );
        $user->activate();

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->authenticatedUser = $user;

        $token = $jwtManager->create($user);

        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));

        return $this->client;
    }

    protected function getAuthenticatedUser(): ?User
    {
        return $this->authenticatedUser;
    }

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->entityManager !== null) {
            $this->entityManager->close();
            $this->entityManager = null;
        }

        static::ensureKernelShutdown();
    }
}
