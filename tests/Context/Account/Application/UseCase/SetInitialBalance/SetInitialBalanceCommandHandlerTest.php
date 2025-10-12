<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Application\UseCase\SetInitialBalance;

use App\Context\Account\Application\UseCase\SetInitialBalance\SetInitialBalanceCommand;
use App\Context\Account\Application\UseCase\SetInitialBalance\SetInitialBalanceCommandHandler;
use App\Context\Account\Domain\Account;
use App\Context\Account\Domain\AccountRepository;
use App\Context\Account\Domain\Bus\InitialBalanceSet;
use App\Tests\Context\Account\Domain\AccountIdMother;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Shared\Domain\Event\EventBus;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SetInitialBalanceCommandHandlerTest extends TestCase
{
    private AccountRepository&MockObject $accountRepository;
    private EventBus&MockObject $eventBus;
    private SetInitialBalanceCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountRepository = $this->createMock(AccountRepository::class);
        $this->eventBus = $this->createMock(EventBus::class);
        $this->handler = new SetInitialBalanceCommandHandler(
            $this->accountRepository,
            $this->eventBus
        );
    }

    /** @test */
    public function test_it_should_set_initial_balance_and_publish_event(): void
    {
        // Arrange
        $accountId = AccountIdMother::create();
        $amount = 100000;
        $date = (new DateTimeImmutable('2024-01-01'))->format('Y-m-d');

        $command = new SetInitialBalanceCommand(
            $accountId->value(),
            $amount,
            $date
        );

        $account = AccountMother::create($accountId);

        $this->accountRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($accountId->value())
            ->willReturn($account);

        $this->eventBus
            ->expects(self::once())
            ->method('publish')
            ->with(self::callback(function (InitialBalanceSet $event) use ($accountId, $amount, $date): bool {
                return $event->aggregateId() === $accountId->value()
                    && $event->amount() === $amount
                    && $event->date() === $date;
            }));

        // Act
        $this->handler->__invoke($command);
    }
}
