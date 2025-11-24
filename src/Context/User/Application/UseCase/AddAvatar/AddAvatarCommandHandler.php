<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\AddAvatar;

use App\Context\User\Domain\UserRepository;
use App\Shared\Application\CommandHandler;
use Symfony\Component\String\Slugger\SluggerInterface;

use function pathinfo;
use function time;
use function unlink;

use const PATHINFO_FILENAME;

final class AddAvatarCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly SluggerInterface $slugger,
        private readonly string $uploadsPath,
    ) {
    }

    public function __invoke(AddAvatarCommand $command): void
    {
        $user = $this->repository->findOneByIdOrFail($command->userId);
        $oldAvatar = $user->avatar();

        $avatar = $command->avatar;
        $originalFilename = pathinfo($avatar->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename)->lower()->toString();
        $newFilename = $safeFilename . '_' . time() . '.' . $avatar->guessExtension();

        $avatar->move(
            $this->uploadsPath,
            $newFilename,
        );

        $user->updateAvatar($newFilename);

        $this->repository->save($user, true);

        if ($oldAvatar) {
            @unlink($this->uploadsPath . '/' . $oldAvatar);
        }
    }
}
