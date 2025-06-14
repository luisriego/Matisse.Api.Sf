<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Application;

use App\Context\Account\Domain\Account;
use App\Context\Account\Domain\AccountRepository;
use App\Shared\Application\EventBus;
use App\Shared\Domain\DomainEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateAccountCommandHandlerTest extends TestCase
{
    private AccountRepository|MockObject|null $repository;
    private EventBus|MockObject|null $eventBus;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AccountRepository::class);
        $this->eventBus = $this->createMock(EventBus::class);
    }

    protected function repository(): AccountRepository|MockObject
    {
        return $this->repository;
    }

    protected function eventBus(): EventBus|MockObject
    {
        return $this->eventBus;
    }

    protected function shouldSave(Account $account): void
    {
        $this->repository->method('save')
            ->with($this->equalTo($account));
    }

    protected function shouldPublishDomainEvent(DomainEvent $event): void
    {
        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(fn($events) =>
            in_array($event, is_array($events) ? $events : [$events])
            ));
    }

    protected function dispatch($command, $handler): void
    {
        $handler($command);
    }
}