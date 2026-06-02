<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Application\UseCase\CreateAccount;

use App\Context\Account\Application\UseCase\CreateAccount\AccountCreator;
use App\Context\Account\Application\UseCase\CreateAccount\CreateAccountCommand;
use App\Context\Account\Application\UseCase\CreateAccount\CreateAccountCommandHandler;
use App\Context\Account\Domain\AccountId;
use App\Context\Account\Domain\AccountName;
use App\Context\Account\Domain\Event\InitialBalanceSet;
use App\Shared\Application\EventStore;
use App\Tests\Context\Account\AccountModuleUnitTestCase;
use App\Tests\Context\Account\Domain\AccountIdMother;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Account\Domain\AccountNameMother;
use Mockery;

final class CreateAccountCommandHandlerTest extends AccountModuleUnitTestCase
{
    private CreateAccountCommandHandler $handler;
    private AccountCreator $creator;

    /** @var EventStore&Mockery\MockInterface */
    private EventStore $eventStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventStore = $this->mock(EventStore::class);
        $this->creator    = new AccountCreator($this->repository());
        $this->handler    = new CreateAccountCommandHandler($this->creator, $this->eventStore);
    }

    /**
     * @test
     */
    public function testItShouldCreateAnAccountAndRecordInitialBalance(): void
    {
        $id   = AccountIdMother::create();
        $name = AccountNameMother::create();

        $command = new CreateAccountCommand(
            $id->value(),
            $name->value(),
            12_500,
            '2026-03-15',
        );

        $account = AccountMother::create(
            new AccountId($command->id()),
            new AccountName($command->name()),
        );

        $this->shouldSave($account);

        $this->eventStore
            ->shouldReceive('append')
            ->once()
            ->with(Mockery::on(static function ($event) use ($command): bool {
                return $event instanceof InitialBalanceSet
                    && $event->aggregateId() === $command->id()
                    && $event->amount() === 12_500
                    && $event->date() === '2026-03-15';
            }));

        $this->handler->__invoke($command);
    }
}
