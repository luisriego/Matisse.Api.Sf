<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\Activation;

use App\Context\User\Domain\User;
use App\Context\User\Domain\UserRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;

use function rtrim;

final readonly class ActivateUserCommandHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private string $appBaseUrl,
        private string $frontSignInPath,
        private string $frontSetPasswordPath,
    ) {}

    /**
     * @throws ResourceNotFoundException
     * @throws InvalidArgumentException
     */
    public function __invoke(ActivateUserCommand $command): ActivateUserResult
    {
        $user = $this->userRepository->findOneByIdOrFail($command->userId());

        if (null === $user) {
            throw ResourceNotFoundException::createFromClassAndId(User::class, $command->userId());
        }

        if ($user->getConfirmationToken() !== $command->token()) {
            throw new InvalidArgumentException('Invalid confirmation token.');
        }

        $user->activate();

        if ($user->needsPasswordSetup()) {
            $user->requestPasswordReset();
            $this->userRepository->save($user, true);

            return new ActivateUserResult($this->buildFrontUrl(
                $this->frontSetPasswordPath,
                $user->getId(),
                $user->getPasswordResetToken(),
            ));
        }

        $this->userRepository->save($user, true);

        return new ActivateUserResult($this->buildFrontUrl($this->frontSignInPath));
    }

    private function buildFrontUrl(string $path, ?string ...$segments): string
    {
        $base = rtrim($this->appBaseUrl, '/');
        $normalizedPath = '/' . trim($path, '/');

        foreach ($segments as $segment) {
            if (null !== $segment && '' !== $segment) {
                $normalizedPath .= '/' . $segment;
            }
        }

        return $base . $normalizedPath;
    }
}
