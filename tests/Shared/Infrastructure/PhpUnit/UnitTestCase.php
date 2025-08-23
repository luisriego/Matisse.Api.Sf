<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\PhpUnit;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\EventBus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class UnitTestCase extends TestCase
{
    protected EventBus|MockObject|null $eventBus = null;

    protected function eventBus(): EventBus|MockObject
    {
        return $this->eventBus ??= $this->createMock(EventBus::class);
    }

    protected function shouldPublishDomainEvents(DomainEvent ...$domainEvents): void
    {
        $this->eventBus()
            ->expects(self::exactly(count($domainEvents)))
            ->method('publish')
            ->with(...$domainEvents);
    }

    protected function shouldNotPublishDomainEvents(): void
    {
        $this->eventBus()
            ->expects(self::never())
            ->method('publish');
    }
}
