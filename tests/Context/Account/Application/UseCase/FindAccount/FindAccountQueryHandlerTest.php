<?php

namespace App\Tests\Context\Account\Application\UseCase\FindAccount;

use App\Context\Account\Application\UseCase\FindAccount\FindAccountQuery;
use App\Context\Account\Application\UseCase\FindAccount\FindAccountQueryHandler;
use App\Context\Account\Domain\AccountId;
use App\Context\Account\Domain\AccountRepository;
use App\Context\Account\Domain\Exception\AccountNotFoundException;
use App\Tests\Context\Account\Domain\AccountIdMother;
use App\Tests\Context\Account\Domain\AccountMother;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class FindAccountQueryHandlerTest extends TestCase
{
    private AccountRepository|MockInterface $repository;
    private FindAccountQueryHandler $handler;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(AccountRepository::class);
        $this->handler = new FindAccountQueryHandler($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testFindAccount(): void
    {
        // Arrange
        $account = AccountMother::create();
        $accountId = new AccountId($account->id());

        $this->repository
            ->shouldReceive('find')
            ->with(Mockery::on(function (AccountId $id) use ($accountId) {
                return $id->value() === $accountId->value();
            }))
            ->once()
            ->andReturn($account);

        $query = new FindAccountQuery($account->id());

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertEquals($account->toArray(), $result);
    }

    public function testFindAccountNotFound(): void
    {
        // Arrange - use a valid UUID format
        $accountId = AccountIdMother::create();

        $this->repository
            ->shouldReceive('find')
            ->with(Mockery::on(function (AccountId $id) use ($accountId) {
                return $id->value() === $accountId->value();
            }))
            ->once()
            ->andThrow(new AccountNotFoundException($accountId->value()));

        $query = new FindAccountQuery($accountId->value());

        // Assert & Act
        $this->expectException(AccountNotFoundException::class);
        ($this->handler)($query);
    }
}