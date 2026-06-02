<?php

declare(strict_types=1);

namespace App\Tests\Context\Account\Application\UseCase\FindAllAccounts;

use App\Context\Account\Application\UseCase\FindAllAccounts\FindAllAccountsQuery;
use App\Context\Account\Application\UseCase\FindAllAccounts\FindAllAccountsQueryHandler;
use App\Context\Account\Domain\AccountRepository;
use App\Tests\Context\Account\Domain\AccountMother;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class FindAllAccountsQueryHandlerTest extends TestCase
{
    private AccountRepository|MockInterface $repository;
    private MockInterface|SerializerInterface $serializer;
    private FindAllAccountsQueryHandler $handler;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(AccountRepository::class);
        $this->serializer = Mockery::mock(SerializerInterface::class);
        $this->handler = new FindAllAccountsQueryHandler($this->repository, $this->serializer);
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

        $account1Data = [
            'id' => $account1->id(),
            'name' => $account1->name(),
            'description' => $account1->description(),
            'isActive' => $account1->isActive(),
        ];

        $account2Data = [
            'id' => $account2->id(),
            'name' => $account2->name(),
            'description' => $account2->description(),
            'isActive' => $account2->isActive(),
        ];

        $this->repository
            ->shouldReceive('findAll')
            ->once()
            ->andReturn($accounts);

        $this->serializer
            ->shouldReceive('normalize')
            ->with(
                Mockery::on(function (array $data) use ($accounts) {
                    return $data === $accounts;
                }),
            )
            ->once()
            ->andReturn([$account1Data, $account2Data]);

        $query = new FindAllAccountsQuery();

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertArrayHasKey('accounts', $result);
        $this->assertArrayHasKey('qtd', $result);
        $this->assertEquals(2, $result['qtd']);
        $this->assertEquals($account1Data, $result['accounts'][0]);
        $this->assertEquals($account2Data, $result['accounts'][1]);
    }

    public function testFindAllAccountsEmptyResult(): void
    {
        // Arrange
        $this->repository
            ->shouldReceive('findAll')
            ->once()
            ->andReturn([]);

        $this->serializer
            ->shouldReceive('normalize')
            ->with([])
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
