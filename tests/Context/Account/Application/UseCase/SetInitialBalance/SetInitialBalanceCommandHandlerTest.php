<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Application\UseCase\SetInitialBalance;

use App\Context\Account\Application\UseCase\SetInitialBalance\SetInitialBalanceCommand;
use App\Context\Account\Application\UseCase\SetInitialBalance\SetInitialBalanceCommandHandler;
use App\Context\Account\Domain\AccountRepository;
use App\Context\Account\Domain\Bus\InitialBalanceSet;
use App\Shared\Application\EventStore; // Changed from EventBus
use App\Tests\Context\Account\Domain\AccountIdMother;
use App\Tests\Context\Account\Domain\AccountMother;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SetInitialBalanceCommandHandlerTest extends TestCase
{
    private AccountRepository&MockObject $accountRepository;
    private EventStore&MockObject $eventStore; // Changed from EventBus
    private SetInitialBalanceCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountRepository = $this->createMock(AccountRepository::class);
        $this->eventStore = $this->createMock(EventStore::class); // Changed from EventBus
        $this->handler = new SetInitialBalanceCommandHandler(
            $this->accountRepository,
            $this->eventStore // Changed from eventBus
        );
    }

    /** @test */
    public function test_it_should_set_initial_balance_and_append_event(): void // Renamed test method
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

        // Expect that the append method is called on the EventStore
        $this->eventStore
            ->expects(self::once())
            ->method('append') // Changed from 'publish'
            ->with(self::callback(function (InitialBalanceSet $event) use ($accountId, $amount, $date): bool {
                return $event->aggregateId() === $accountId->value()
                    && $event->amount() === $amount
                    && $event->date() === $date;
            }));

        // Act
        $this->handler->__invoke($command);
    }
}
