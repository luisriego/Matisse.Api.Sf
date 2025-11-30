<?php

declare(strict_types=1);

namespace App\Context\User\Application\UseCase\AddAvatar;

use App\Shared\Application\Command;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class AddAvatarCommand implements Command
{
    public function __construct(
        public string $userId,
        public UploadedFile $avatar,
    ) {}
}
