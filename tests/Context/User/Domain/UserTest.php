<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Domain;

use App\Context\User\Domain\Event\UserWasRegistered;
use App\Context\User\Domain\User;
use App\Tests\Context\User\Domain\ValueObject\EmailMother;
use App\Tests\Context\User\Domain\ValueObject\PasswordMother;
use App\Tests\Context\User\Domain\ValueObject\UserIdMother;
use App\Tests\Context\User\Domain\ValueObject\UserNameMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserTest extends TestCase
{
    public function testItShouldCreateAUserCorrectly(): void
    {
        $id = UserIdMother::create();
        $name = UserNameMother::create();
        $email = EmailMother::create();
        $password = PasswordMother::create();

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('hashed_password');

        $user = User::create($id, $name, $email, $password, $hasher);

        $this->assertSame($id->value(), $user->getId());
        $this->assertSame($name->value(), $user->getName());
        $this->assertSame($email->value(), $user->getEmail());
        $this->assertSame('hashed_password', $user->getPassword());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testItShouldRecordADomainEventOnCreation(): void
    {
        $user = UserMother::createRandom();
        $events = $user->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(UserWasRegistered::class, $events[0]);
    }

    // ──────────────────────────────────────────────────────────────
    // TEN-01 — Promoción a síndico mantiene ROLE_SYNDIC único
    // ──────────────────────────────────────────────────────────────

    public function testPromoteToSyndicGuaranteesSingleRoleSyndic(): void
    {
        $user = UserMother::createRandom();

        // Initial state: only ROLE_USER
        $this->assertFalse($user->isSyndic());
        $rolesBefore = $user->getRoles();
        $this->assertSame(['ROLE_USER'], $rolesBefore);

        // First promotion
        $user->promoteToSyndic();
        $this->assertTrue($user->isSyndic());
        $rolesAfterFirst = $user->getRoles();
        $this->assertContains('ROLE_USER', $rolesAfterFirst);
        $this->assertContains('ROLE_SYNDIC', $rolesAfterFirst);
        $this->assertCount(2, $rolesAfterFirst);

        // Repeated promotions must NOT duplicate ROLE_SYNDIC
        $user->promoteToSyndic();
        $user->promoteToSyndic();
        $user->promoteToSyndic();
        $this->assertTrue($user->isSyndic());
        $rolesAfterRepeated = $user->getRoles();
        $this->assertContains('ROLE_USER', $rolesAfterRepeated);
        $this->assertContains('ROLE_SYNDIC', $rolesAfterRepeated);
        $this->assertCount(2, $rolesAfterRepeated);

        // Count of ROLE_SYNDIC must be exactly 1
        $syndicCount = array_filter($rolesAfterRepeated, static fn (string $r): bool => $r === 'ROLE_SYNDIC');
        $this->assertCount(1, $syndicCount);
    }

    public function testDemoteToResidentRemovesRoleSyndicLeavingRoleUser(): void
    {
        $user = UserMother::createRandom();

        // Promote to syndic first
        $user->promoteToSyndic();
        $this->assertTrue($user->isSyndic());

        // Demote back to resident
        $user->demoteToResident();
        $this->assertFalse($user->isSyndic());

        $rolesAfterDemote = $user->getRoles();
        $this->assertSame(['ROLE_USER'], array_values(array_filter($rolesAfterDemote, static fn (string $r): bool => $r !== 'ROLE_SYNDIC')));
        $this->assertNotContains('ROLE_SYNDIC', $rolesAfterDemote);
    }

    public function testDemoteToResidentWhenNotSyndicIsIdempotent(): void
    {
        $user = UserMother::createRandom();

        // Demoting a non-syndic should not change roles
        $user->demoteToResident();
        $this->assertFalse($user->isSyndic());
        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }
}
