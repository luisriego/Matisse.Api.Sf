<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\AddAvatar\AddAvatarCommand;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

use function in_array;

final class UploadAvatarController extends ApiController
{
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif'];

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $avatar = $request->files->get('avatar');

        if (!$avatar instanceof UploadedFile) {
            throw new InvalidArgumentException('Avatar inválido');
        }

        if (!in_array($avatar->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('Tipo de fichero no válido. Solo se admiten JPEG, PNG y GIF.');
        }

        try {
            $this->dispatch(new AddAvatarCommand(
                userId: $id,
                avatar: $avatar,
            ));
        } catch (HandlerFailedException $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof ResourceNotFoundException) {
                throw $previous;
            }

            throw $e;
        }

        return new JsonResponse(null, Response::HTTP_OK);
    }

    public function exceptions(): array
    {
        return [
            InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
            ResourceNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
