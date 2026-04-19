<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\PhpUnit;

use App\Context\User\Domain\User;
use App\Kernel;
use App\Tests\Context\User\Domain\ValueObject\EmailMother;
use App\Tests\Context\User\Domain\ValueObject\PasswordMother;
use App\Tests\Context\User\Domain\ValueObject\UserIdMother;
use App\Tests\Context\User\Domain\ValueObject\UserNameMother;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
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
     *
     * Safety: refuses to run against a non-dedicated DB name (see assertDatabaseUrlIsSafeForDestructiveTests).
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::bootKernel(['environment' => 'test']);
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $connection    = $entityManager->getConnection();

        static::assertConnectedDatabaseIsSafeForDestructiveTests($connection);

        $connection->executeStatement('SET session_replication_role = replica');
        foreach ($entityManager->getMetadataFactory()->getAllMetadata() as $meta) {
            $connection->executeStatement(sprintf('TRUNCATE TABLE %s CASCADE', $meta->getTableName()));
        }
        $connection->executeStatement('SET session_replication_role = DEFAULT');

        // Commit the TRUNCATE so DAMA's per-test beginTransaction() works on a clean slate.
        // Only needed the first time the connection is created (it starts with an outer BEGIN).
        $nativeConn = $connection->getNativeConnection();
        if ($nativeConn instanceof \PDO && $nativeConn->inTransaction()) {
            StaticDriver::commit();
        }

        static::ensureKernelShutdown();
    }

    /**
     * Integration tests wipe every mapped table. Uses the real connected DB name (PostgreSQL)
     * so we never TRUNCATE dev/prod by mistake.
     */
    private static function assertConnectedDatabaseIsSafeForDestructiveTests(Connection $connection): void
    {
        $platform = $connection->getDatabasePlatform();
        if ($platform instanceof SQLitePlatform) {
            return;
        }

        if (!$platform instanceof PostgreSQLPlatform) {
            throw new \RuntimeException(
                'ApiTestCase safety check: add rules for your DB platform or use SQLite/PostgreSQL.',
            );
        }

        $dbName = (string) $connection->fetchOne('SELECT current_database()');

        if (str_ends_with($dbName, '_test_test')) {
            throw new \RuntimeException(sprintf(
                'Database name %s looks like a double suffix (Doctrine dbname_suffix + .../app_test in DATABASE_URL). '
                . 'Use a single suffix in the URL (e.g. .../app_test) and set config/packages/test/doctrine.yaml dbname_suffix to empty.',
                $dbName,
            ));
        }

        $allowed = $dbName === 'test_db' || str_ends_with($dbName, '_test');

        if (!$allowed) {
            throw new \RuntimeException(sprintf(
                'Refusing to TRUNCATE PostgreSQL database %s: integration tests require a dedicated DB '
                . '(name must end with _test, e.g. app_test, or test_db for CI). '
                . 'Point .env.test DATABASE_URL to a different database than dev. See .env.test comments.',
                $dbName,
            ));
        }
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

    protected function createAuthenticatedClient(?string $email = null, string $password = 'password'): KernelBrowser
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
