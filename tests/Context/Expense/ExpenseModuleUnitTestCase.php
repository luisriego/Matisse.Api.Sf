<?php

declare(strict_types=1);

namespace App\Tests\Context\Expense;

use App\Context\Expense\Domain\Expense;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Shared\Application\EventBus;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\MockObject\Exception;

abstract class ExpenseModuleUnitTestCase extends MockeryTestCase
{
    private ExpenseRepository|MockInterface $repository;
    private EventBus|MockInterface $eventBus;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ExpenseRepository::class);
        $this->eventBus = $this->createMock(EventBus::class);
    }

    protected function repository(): ExpenseRepository|MockInterface
    {
        return $this->repository;
    }

    protected function eventBus(): EventBus|MockInterface
    {
        return $this->eventBus;
    }

    protected function shouldSave(Expense $expense): void
    {
        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($expense, true);
    }

    protected function shouldFindOneByIdOrFail(string $id, Expense $expense): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($id)
            ->willReturn($expense);
    }

    protected function shouldPublishDomainEvents(array $events): void
    {
        if (!empty($events)) {
            $this->eventBus
                ->expects($this->once())
                ->method('publish')
                ->with(...$events);
        }
    }
}