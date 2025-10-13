<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\Activation;

use App\Context\User\Domain\UserRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;

final class ActivateUserCommandHandler implements CommandHandler
{
    public function __construct(private readonly UserRepository $userRepository) {}

    public function __invoke(ActivateUserCommand $command): void
    {
        $user = $this->userRepository->findOneByIdOrFail($command->userId());

        // Aunque findOneByIdOrFail ya lanza una excepción, esta es una salvaguarda adicional.
        if (null === $user) {
            throw ResourceNotFoundException::createFromClassAndId(User::class, $command->userId());
        }

        if ($user->getConfirmationToken() !== $command->token()) {
            throw new InvalidArgumentException('Invalid confirmation token.');
        }

        $user->activate();

        $this->userRepository->save($user, true);
    }
}
