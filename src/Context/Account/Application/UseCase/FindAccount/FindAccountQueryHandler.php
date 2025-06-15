<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\FindAccount;

use App\Context\Account\Domain\AccountId;
use App\Context\Account\Domain\AccountRepository;
use App\Shared\Application\QueryHandler;

final readonly class FindAccountQueryHandler implements QueryHandler
{
    public function __construct(private AccountRepository $repository) {}

    public function __invoke(FindAccountQuery $query): array
    {
        $accountId = new AccountId($query->id());
        $account = $this->repository->find($accountId);

        return $account->toArray();
    }
}
