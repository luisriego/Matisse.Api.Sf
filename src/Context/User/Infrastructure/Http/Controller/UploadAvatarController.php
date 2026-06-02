<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Http\Controller;

use App\Context\User\Application\UseCase\AddAvatar\AddAvatarCommand;
use App\Context\User\Application\UseCase\AddAvatar\AddAvatarCommandHandler;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function in_array;

#[OA\Post(
    path: '/api/v1/users/{id}/avatar',
    summary: 'Upload user avatar',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['avatar'],
                properties: [
                    new OA\Property(
                        property: 'avatar',
                        type: 'string',
                        format: 'binary',
                        description: 'Avatar image (JPEG, PNG or GIF)',
                    ),
                ],
            ),
        ),
    ),
    tags: ['Users'],
    security: [['bearerAuth' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Avatar uploaded.'),
        new OA\Response(response: 400, description: 'Invalid file.'),
        new OA\Response(response: 404, description: 'User not found.'),
        new OA\Response(response: 401, description: 'Unauthorized'),
    ],
)]
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
