<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\AddAvatar\AddAvatarCommand;
use App\Context\User\Application\UseCase\AddAvatar\AddAvatarCommandHandler;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function in_array;

final readonly class UploadAvatarController
{
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif'];

    public function __construct(
        private AddAvatarCommandHandler $commandHandler,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $avatar = $request->files->get('avatar');

        if (!$avatar instanceof UploadedFile) {
            return new JsonResponse(['error' => 'Avatar inválido'], Response::HTTP_BAD_REQUEST);
        }

        if (!in_array($avatar->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            return new JsonResponse(
                ['error' => 'Tipo de fichero no válido. Solo se admiten JPEG, PNG y GIF.'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            ($this->commandHandler)(new AddAvatarCommand(userId: $id, avatar: $avatar));
        } catch (ResourceNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(null, Response::HTTP_OK);
    }
}
