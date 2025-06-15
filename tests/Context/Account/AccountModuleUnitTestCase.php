<?php

declare(strict_types=1);

namespace App\Tests\Context\Account;

use App\Context\Account\Domain\Account;
use App\Context\Account\Domain\AccountId;
use App\Context\Account\Domain\AccountRepository;
use App\Tests\Shared\Infrastructure\UnitTestCase;
use Mockery\MockInterface;

abstract class AccountModuleUnitTestCase extends UnitTestCase
{
    private AccountRepository | MockInterface | null $repository = null;

    protected function shouldSave(Account $account): void
    {
        $this->repository()
            ->shouldReceive('save')
            ->with($this->similarTo($account))
            ->once()
            ->andReturnNull();
    }

    protected function shouldSearch(AccountId $id, ?Account $account): void
    {
        $this->repository()
            ->shouldReceive('search')
            ->with($this->similarTo($id))
            ->once()
            ->andReturn($account);
    }

    protected function repository(): AccountRepository | MockInterface
    {
        return $this->repository ??= $this->mock(AccountRepository::class);
    }
}