<?php

declare(strict_types=1);

namespace App\Context\Account\Application\UseCase\FindAccount;

use App\Context\Account\Domain\AccountRepository;
use App\Shared\Application\QueryHandler;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class FindAccountQueryHandler implements QueryHandler
{
    public function __construct(
        private AccountRepository $repository,
        private SerializerInterface $serializer,
    ) {}

    public function __invoke(FindAccountQuery $query): array
    {
        $account = $this->repository->findOneByIdOrFail($query->id());

        return $this->serializer->normalize($account);
    }
}
