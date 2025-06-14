<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure;

use App\Shared\Application\EventBus;
use Mockery\MockInterface;

abstract class UnitTestCase extends MockeryTestCase
{
    private $eventBus;

    protected function eventBus(): EventBus|MockInterface
    {
        return $this->eventBus ??= $this->mock(EventBus::class);
    }

    protected function shouldPublishDomainEvents(array $events): void
    {
        if (empty($events)) {
            // The key issue - we should allow publish to be called but with empty events
            $this->eventBus()
                ->shouldReceive('publish')
                ->withNoArgs()
                ->zeroOrMoreTimes();
            return;
        }

        $this->eventBus()
            ->shouldReceive('publish')
            ->with(...$events)
            ->once();
    }
}