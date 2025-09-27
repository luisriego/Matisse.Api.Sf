<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\FindUsers;

use App\Context\User\Domain\UserRepository;
use App\Shared\Application\QueryHandler;

final class FindUsersQueryHandler implements QueryHandler
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function __invoke(FindUsersQuery $query): ?array
    {
        return $this->userRepository->findAll();
    }
}
