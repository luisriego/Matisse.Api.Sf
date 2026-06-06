<?php

declare(strict_types=1);

namespace App\Tests\Context\User\Application\UseCase\InviteResident;

use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\User\Application\UseCase\InviteResident\InviteResidentFromUnitCommand;
use App\Context\User\Application\UseCase\InviteResident\InviteResidentFromUnitCommandHandler;
use App\Context\User\Domain\User;
use App\Context\User\Domain\UserRepository;
use App\Context\User\Domain\ValueObject\Email;
use App\Context\User\Domain\ValueObject\UserId;
use App\Context\User\Domain\ValueObject\UserName;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class InviteResidentFromUnitCommandHandlerTest extends TestCase
{
    private MockObject|ResidentUnitRepository $residentUnitRepository;
    private MockObject|UserRepository $userRepository;
    private InviteResidentFromUnitCommandHandler $handler;

    protected function setUp(): void
    {
        $this->residentUnitRepository = $this->createMock(ResidentUnitRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->handler = new InviteResidentFromUnitCommandHandler(
            $this->residentUnitRepository,
            $this->userRepository,
        );
    }

    public function testItShouldInviteResidentAndPersistUser(): void
    {
        $unit = ResidentUnitMother::create();
        $command = new InviteResidentFromUnitCommand($unit->id(), 'joao@example.com', 'João');

        $this->residentUnitRepository
            ->expects($this->once())
            ->method('findOneById')
            ->with($unit->id())
            ->willReturn($unit);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('joao@example.com')
            ->willReturn(null);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with(self::callback(static function (User $user): bool {
                return false === $user->isActive()
                    && $user->needsPasswordSetup()
                    && 'joao@example.com' === $user->getEmail();
            }), true);

        ($this->handler)($command);
    }

    public function testItShouldThrowWhenUnitNotFound(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $this->residentUnitRepository
            ->method('findOneById')
            ->willReturn(null);

        ($this->handler)(new InviteResidentFromUnitCommand('missing-id', 'a@b.com'));
    }

    public function testItShouldSyncExistingUserToUnitAndUpdateName(): void
    {
        $unit = ResidentUnitMother::create();
        $existingUser = User::invite(
            UserId::fromString((string) Uuid::random()),
            UserName::fromString('Old Name'),
            Email::fromString('existing@example.com'),
            $unit,
        );

        $this->residentUnitRepository
            ->method('findOneById')
            ->willReturn($unit);

        $this->userRepository
            ->method('findByEmail')
            ->willReturn($existingUser);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($existingUser, true);

        ($this->handler)(new InviteResidentFromUnitCommand($unit->id(), 'existing@example.com', 'Luis'));

        self::assertSame('Luis', $existingUser->getName());
    }
}
