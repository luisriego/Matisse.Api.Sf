<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\FindUser;

use App\Context\User\Domain\User;
use App\Context\User\Domain\UserRepository;
use App\Shared\Application\QueryHandler;

final class FindUserQueryHandler implements QueryHandler
{
    public function __construct(private readonly UserRepository $userRepository) {}

    public function __invoke(FindUserQuery $query): ?User
    {
        return $this->userRepository->findOneById($query->id());
    }
}
