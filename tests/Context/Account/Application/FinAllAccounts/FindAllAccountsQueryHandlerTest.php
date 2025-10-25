<?php

namespace App\Tests\Context\Account\Application\UseCase\FindAllAccounts;

use App\Context\Account\Application\AccountResponse;
use App\Context\Account\Application\AccountTransformer;
use App\Context\Account\Application\UseCase\FindAllAccounts\FindAllAccountsQuery;
use App\Context\Account\Application\UseCase\FindAllAccounts\FindAllAccountsQueryHandler;
use App\Context\Account\Domain\AccountRepository;
use App\Tests\Context\Account\Domain\AccountMother;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class FindAllAccountsQueryHandlerTest extends TestCase
{
    private AccountRepository|MockInterface $repository;
    private AccountTransformer|MockInterface $transformer;
    private FindAllAccountsQueryHandler $handler;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(AccountRepository::class);
        $this->transformer = Mockery::mock(AccountTransformer::class);
        $this->handler = new FindAllAccountsQueryHandler($this->repository, $this->transformer);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testFindAllAccounts(): void
    {
        // Arrange
        $account1 = AccountMother::create();
        $account2 = AccountMother::create();
        $accounts = [$account1, $account2];

        $this->repository
            ->shouldReceive('findAll')
            ->once()
            ->andReturn($accounts);

        $query = new FindAllAccountsQuery();

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertArrayHasKey('accounts', $result);
        $this->assertArrayHasKey('qtd', $result);
        $this->assertEquals(2, $result['qtd']);
        $this->assertEquals($account1->toArray(), $result['accounts'][0]);
        $this->assertEquals($account2->toArray(), $result['accounts'][1]);
    }

    public function testFindAllAccountsEmptyResult(): void
    {
        // Arrange
        $this->repository
            ->shouldReceive('findAll')
            ->once()
            ->andReturn([]);

        $query = new FindAllAccountsQuery();

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertArrayHasKey('accounts', $result);
        $this->assertArrayHasKey('qtd', $result);
        $this->assertEquals(0, $result['qtd']);
        $this->assertEmpty($result['accounts']);
    }
}