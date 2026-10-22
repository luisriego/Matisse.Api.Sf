<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Infrastructure\Console;

use App\Context\User\Domain\User;
use App\Context\User\Domain\UserRepository;
use App\Tests\Context\User\Domain\ValueObject\EmailMother;
use App\Tests\Context\User\Domain\ValueObject\PasswordMother;
use App\Tests\Context\User\Domain\ValueObject\UserIdMother;
use App\Tests\Context\User\Domain\ValueObject\UserNameMother;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class SetSyndicCommandTest extends ApiTestCase
{
    private \Symfony\Component\Console\Tester\CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $userRepository = self::getContainer()->get(\App\Context\User\Domain\UserRepository::class);
        $command = new \App\Context\User\Infrastructure\Console\SetSyndicCommand($userRepository);
        $this->commandTester = new CommandTester($command);
    }

    public function testNewSyndicPromotedAndPreviousDemoted(): void
    {
        $hasher = self::getContainer()->get(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class);

        // Create user A (current syndic)
        $userA = User::create(
            UserIdMother::create(),
            UserNameMother::create('Old Syndic'),
            EmailMother::create('old@example.com'),
            PasswordMother::create('password'),
            $hasher,
        );
        $userA->activate();
        $userA->promoteToSyndic();
        $this->entityManager->persist($userA);

        // Create user B (resident)
        $userB = User::create(
            UserIdMother::create(),
            UserNameMother::create('New Syndic'),
            EmailMother::create('new@example.com'),
            PasswordMother::create('password'),
            $hasher,
        );
        $userB->activate();
        $this->entityManager->persist($userB);

        $this->entityManager->flush();

        // Verify initial state: user A is syndic
        $userRepository = self::getContainer()->get(UserRepository::class);
        $this->assertTrue($userRepository->findOneByIdOrFail($userA->getId())->isSyndic());
        $this->assertFalse($userRepository->findOneByIdOrFail($userB->getId())->isSyndic());

        // Execute command to set user B as syndic
        $result = $this->commandTester->execute(['email' => 'new@example.com']);

        $this->assertSame(Command::SUCCESS, $result);
        $this->assertStringContainsString('is now the syndic', $this->commandTester->getDisplay());

        // Reload from database and verify
        $this->entityManager->clear();
        $this->entityManager->getConnection()->executeStatement('SELECT 1'); // detach proxy cache

        $userAReloaded = $userRepository->findOneByIdOrFail($userA->getId());
        $userBReloaded = $userRepository->findOneByIdOrFail($userB->getId());

        // User B is now syndic
        $this->assertTrue($userBReloaded->isSyndic());
        // User A was demoted
        $this->assertFalse($userAReloaded->isSyndic());

        // Only one syndic in database
        $syndics = $userRepository->findSyndics();
        $this->assertCount(1, $syndics);
        $this->assertSame($userB->getId(), $syndics[0]->getId());
    }

    public function testCommandFailsWhenEmailNotFound(): void
    {
        $result = $this->commandTester->execute(['email' => 'nonexistent@example.com']);

        $this->assertSame(Command::FAILURE, $result);
        $this->assertStringContainsString('No user found', $this->commandTester->getDisplay());
    }

    public function testSettingSameUserAsSyndicIsIdempotent(): void
    {
        $hasher = self::getContainer()->get(\Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface::class);

        // Create user A (current syndic)
        $userA = User::create(
            UserIdMother::create(),
            UserNameMother::create('Current Syndic'),
            EmailMother::create('current@example.com'),
            PasswordMother::create('password'),
            $hasher,
        );
        $userA->activate();
        $userA->promoteToSyndic();
        $this->entityManager->persist($userA);

        $this->entityManager->flush();

        // Execute command again with same user
        $result = $this->commandTester->execute(['email' => 'current@example.com']);

        $this->assertSame(Command::SUCCESS, $result);

        // Still only one syndic (the same)
        $userRepository = self::getContainer()->get(UserRepository::class);
        $syndics = $userRepository->findSyndics();
        $this->assertCount(1, $syndics);
        $this->assertSame($userA->getId(), $syndics[0]->getId());
    }
}
