<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Shared\Application\EventBus;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

abstract class ResidentUnitTestCase extends MockeryTestCase
{
    private MockInterface $repository;
    private MockInterface $eventBus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(ResidentUnitRepository::class);
        $this->eventBus = Mockery::mock(EventBus::class);
    }

    protected function repository(): MockInterface
    {
        return $this->repository;
    }

    protected function eventBus(): MockInterface
    {
        return $this->eventBus;
    }

    protected function shouldSave(ResidentUnit $residentUnit): void
    {
        $this->repository
            ->shouldReceive('save')
            ->once()
            ->with(\Mockery::on(function(ResidentUnit $actual) use ($residentUnit) {
                return $actual->id() === $residentUnit->id() &&
                    $actual->getUnit() === $residentUnit->getUnit() &&
                    $actual->idealFraction() === $residentUnit->idealFraction() &&
                    $actual->isActive() === $residentUnit->isActive();
            }), true);
    }

    protected function shouldPublishDomainEvents(array $events): void
    {
        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->withArgs($events);
    }
}