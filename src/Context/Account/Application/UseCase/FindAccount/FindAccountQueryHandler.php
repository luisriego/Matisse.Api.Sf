<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\FindAccount;

use App\Context\Account\Application\AccountResponse;
use App\Context\Account\Application\AccountTransformer;
use App\Context\Account\Domain\AccountId;
use App\Context\Account\Domain\AccountRepository;
use App\Shared\Application\QueryHandler;

final readonly class FindAccountQueryHandler implements QueryHandler
{
    public function __construct(
        private AccountRepository $repository,
        private AccountTransformer $transformer
    ) {}

    public function __invoke(FindAccountQuery $query): AccountResponse
    {
        $accountId = new AccountId($query->id());
        $account = $this->repository->find($accountId);

        return $this->transformer->transform($account);
    }
}
