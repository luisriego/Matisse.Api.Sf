<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Domain;

use App\Context\User\Domain\Event\CreateUserDomainEvent;
use App\Context\User\Domain\User;
use App\Tests\Context\User\Domain\ValueObject\EmailMother;
use App\Tests\Context\User\Domain\ValueObject\PasswordMother;
use App\Tests\Context\User\Domain\ValueObject\UserIdMother;
use App\Tests\Context\User\Domain\ValueObject\UserNameMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserTest extends TestCase
{
    public function test_it_should_create_a_user_correctly(): void
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

    public function test_it_should_record_a_domain_event_on_creation(): void
    {
        $user = UserMother::createRandom();
        $events = $user->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(CreateUserDomainEvent::class, $events[0]);
    }
}
