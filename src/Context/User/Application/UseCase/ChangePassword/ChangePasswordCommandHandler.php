<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\ChangePassword;

use App\Context\User\Domain\UserRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ChangePasswordCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(ChangePasswordCommand $command): void
    {
        $user = $this->userRepository->findByEmail($command->userId());

        if (null === $user) {
            throw new ResourceNotFoundException('User not found.');
        }

        // Check if the old password is valid
        if (!$this->passwordHasher->isPasswordValid($user, $command->oldPassword())) {
            throw new InvalidArgumentException('Invalid old password.');
        }

        // Hash and set the new password
        $user->changePassword($command->newPassword(), $this->passwordHasher);

        $this->userRepository->save($user, true);
    }
}
