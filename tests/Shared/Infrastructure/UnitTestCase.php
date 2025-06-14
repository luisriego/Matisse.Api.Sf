<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure;

abstract class UnitTestCase extends MockeryTestCase
{
    private EventBus | MockInterface | null $eventBus = null;
    private MockInterface | UuidGenerator | null $uuidGenerator = null;

    protected function mock(string $className): MockInterface
    {
        return Mockery::mock($className);
    }

    protected function shouldPublishDomainEvent(DomainEvent $domainEvent): void
    {
        $this->eventBus()
            ->shouldReceive('publish')
            ->with($this->similarTo($domainEvent))
            ->andReturnNull();
    }

    protected function shouldNotPublishDomainEvent(): void
    {
        $this->eventBus()
            ->shouldReceive('publish')
            ->withNoArgs()
            ->andReturnNull();
    }

    protected function eventBus(): EventBus | MockInterface
    {
        return $this->eventBus ??= $this->mock(EventBus::class);
    }

    protected function shouldGenerateUuid(string $uuid): void
    {
        $this->uuidGenerator()
            ->shouldReceive('generate')
            ->once()
            ->withNoArgs()
            ->andReturn($uuid);
    }

    protected function uuidGenerator(): MockInterface | UuidGenerator
    {
        return $this->uuidGenerator ??= $this->mock(UuidGenerator::class);
    }

    protected function notify(DomainEvent $event, callable $subscriber): void
    {
        $subscriber($event);
    }

    protected function dispatch(Command $command, callable $commandHandler): void
    {
        $commandHandler($command);
    }

    protected function assertAskResponse(Response $expected, Query $query, callable $queryHandler): void
    {
        $actual = $queryHandler($query);

        $this->assertEquals($expected, $actual);
    }

    /** @param class-string<Throwable> $expectedErrorClass */
    protected function assertAskThrowsException(string $expectedErrorClass, Query $query, callable $queryHandler): void
    {
        $this->expectException($expectedErrorClass);

        $queryHandler($query);
    }

    protected function isSimilar(mixed $expected, mixed $actual): bool
    {
        return TestUtils::isSimilar($expected, $actual);
    }

    protected function assertSimilar(mixed $expected, mixed $actual): void
    {
        TestUtils::assertSimilar($expected, $actual);
    }

    protected function similarTo(mixed $value, float $delta = 0.0): CodelyTvMatcherIsSimilar
    {
        return TestUtils::similarTo($value, $delta);
    }
}