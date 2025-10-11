<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\Update;

use App\Context\User\Domain\UserRepository;
use App\Shared\Application\CommandHandler;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

final class UpdateUserCommandHandler implements CommandHandler
{
    public function __construct(private readonly UserRepository $userRepository) {}

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $this->userRepository->findOneById($command->getId());

        if (null === $user) {
            throw new UserNotFoundException();
        }

        $user->update(
            $command->getName(),
            $command->getLastName(),
            $command->getGender(),
            $command->getPhoneNumber(),
        );

        $this->userRepository->save($user, true);
    }
}
