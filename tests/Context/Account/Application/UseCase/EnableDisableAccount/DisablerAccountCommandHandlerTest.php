<?php

namespace App\Tests\Context\Account\Application\UseCase\EnableDisableAccount;

use App\Context\Account\Application\UseCase\DisableAccount\DisableAccountCommand;
use App\Context\Account\Application\UseCase\DisableAccount\DisableAccountCommandHandler;
use App\Context\Account\Domain\AccountRepository;
use App\Shared\Application\EventBus;
use App\Tests\Context\Account\Domain\AccountMother;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DisablerAccountCommandHandlerTest extends TestCase
{
    private AccountRepository|MockObject $repository;
    private EventBus|MockObject $eventBus;
    private DisableAccountCommandHandler $handler;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->repository = $this->createMock(AccountRepository::class);
        $this->eventBus = $this->createMock(EventBus::class);
        $this->handler = new DisableAccountCommandHandler($this->repository, $this->eventBus);
    }

    public function testDisableAccount(): void
    {
        // Arrange
        $account = AccountMother::create();
        $account->enable(); // Make sure it's active before disabling

        $command = new DisableAccountCommand($account->id());

        $this->repository
            ->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($command->id())
            ->willReturn($account);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($account);

        $this->eventBus
            ->expects($this->once())
            ->method('publish');

        // Act
        ($this->handler)($command);

        // Assert
        $this->assertFalse($account->isActive());
    }
}