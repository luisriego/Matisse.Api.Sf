<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\ResidentUnit;

use App\Context\User\Domain\UserRepository;

final readonly class UnlinkResidentUnitFromUserCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function __invoke(UnlinkResidentUnitFromUserCommand $command): void
    {
        $user = $this->userRepository->findOneById($command->userId());
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        if ($user->getResidentUnit() === null) {
            return;
        }

        $user->setResidentUnit(null);

        $this->userRepository->save($user, true);
    }
}