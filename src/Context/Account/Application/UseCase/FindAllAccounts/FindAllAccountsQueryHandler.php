<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\FindAllAccounts;

use App\Context\Account\Domain\AccountRepository;
use App\Shared\Application\QueryHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Serializer\SerializerInterface;

use function count;

#[AsMessageHandler(bus: 'query.bus')]
readonly class FindAllAccountsQueryHandler implements QueryHandler
{
    public function __construct(
        private AccountRepository $repository,
        private SerializerInterface $serializer
    ) {}

    public function __invoke(FindAllAccountsQuery $query): array
    {
        $accounts = $this->repository->findAll();

        $accountsArray = $this->serializer->normalize($accounts);

        return [
            'accounts' => $accountsArray,
            'qtd' => count($accountsArray),
        ];
    }
}
