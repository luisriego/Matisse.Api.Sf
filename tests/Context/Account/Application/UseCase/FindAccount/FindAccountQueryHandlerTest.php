<?php

namespace App\Tests\Context\Account\Application\UseCase\FindAccount;

use App\Context\Account\Application\UseCase\FindAccount\FindAccountQuery;
use App\Context\Account\Application\UseCase\FindAccount\FindAccountQueryHandler;
use App\Context\Account\Domain\Account;
use App\Context\Account\Domain\AccountRepository;
use App\Context\Account\Domain\Exception\AccountNotFoundException;
use App\Tests\Context\Account\Domain\AccountIdMother;
use App\Tests\Context\Account\Domain\AccountMother;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class FindAccountQueryHandlerTest extends TestCase
{
    private AccountRepository|MockInterface $repository;
    private SerializerInterface|MockInterface $serializer;
    private FindAccountQueryHandler $handler;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(AccountRepository::class);
        $this->serializer = Mockery::mock(SerializerInterface::class);
        $this->handler = new FindAccountQueryHandler($this->repository, $this->serializer);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testFindAccount(): void
    {
        // Arrange
        $account = AccountMother::create();
        $accountId = $account->id();
        $accountData = [
            'id' => $accountId,
            'code' => $account->code(),
            'name' => $account->name(),
            'description' => $account->description(),
            'isActive' => $account->isActive(),
        ];

        $this->repository
            ->shouldReceive('findOneByIdOrFail')
            ->with($accountId)
            ->once()
            ->andReturn($account);

        $this->serializer
            ->shouldReceive('normalize')
            ->with(
                Mockery::on(function (Account $object) use ($account) {
                    return $object->id() === $account->id();
                })
            )
            ->once()
            ->andReturn($accountData);

        $query = new FindAccountQuery($accountId);

        // Act
        $result = ($this->handler)($query);

        // Assert
        $this->assertSame($accountData, $result);
    }

    public function testFindAccountNotFound(): void
    {
        // Arrange
        $accountId = AccountIdMother::create()->value();

        $this->repository
            ->shouldReceive('findOneByIdOrFail')
            ->with($accountId)
            ->once()
            ->andThrow(new AccountNotFoundException($accountId));

        $query = new FindAccountQuery($accountId);

        // Assert & Act
        $this->expectException(AccountNotFoundException::class);
        ($this->handler)($query);
    }
}
