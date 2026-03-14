<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Application\UseCase\FindUser;

use App\Context\User\Application\UseCase\FindUser\FindUserQuery;
use App\Context\User\Application\UseCase\FindUser\FindUserQueryHandler;
use App\Context\User\Domain\User;
use App\Context\User\Domain\UserRepository;
use App\Tests\Context\User\Domain\UserMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class FindUserQueryHandlerTest extends TestCase
{
    private MockObject|UserRepository $userRepository;
    private FindUserQueryHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->handler = new FindUserQueryHandler($this->userRepository);
    }

    public function test_it_should_return_user_when_found(): void
    {
        $user = UserMother::createRandom();
        $query = new FindUserQuery($user->getId());

        $this->userRepository
            ->expects($this->once())
            ->method('findOneById')
            ->with($user->getId())
            ->willReturn($user);

        $result = ($this->handler)($query);

        $this->assertSame($user, $result);
    }

    public function test_it_should_return_null_when_user_not_found(): void
    {
        $query = new FindUserQuery('non-existent-uuid');

        $this->userRepository
            ->expects($this->once())
            ->method('findOneById')
            ->with('non-existent-uuid')
            ->willReturn(null);

        $result = ($this->handler)($query);

        $this->assertNull($result);
    }
}