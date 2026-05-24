<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\AddExpenseAttachment\AddExpenseAttachmentCommand;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[OA\Post(
    path: '/api/v1/expenses/attachment/{id}',
    summary: 'Upload expense attachment',
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['attachment'],
                properties: [
                    new OA\Property(property: 'attachment', type: 'string', format: 'binary'),
                ],
            ),
        ),
    ),
    tags: ['Expenses'],
    security: [['bearerAuth' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Attachment uploaded'),
        new OA\Response(response: 400, description: 'Invalid attachment'),
        new OA\Response(response: 401, description: 'Unauthorized'),
        new OA\Response(response: 404, description: 'Expense not found'),
    ],
)]
final class ExpenseAttachmentPatchController extends ApiController
{
    public function __invoke(Request $request, string $id): JsonResponse
    {
        $attachment = $request->files->get('attachment');

        if (!$attachment instanceof UploadedFile) {
            throw new InvalidArgumentException('Anexo inválido');
        }

        $this->dispatch(new AddExpenseAttachmentCommand(
            expenseId: $id,
            attachment: $attachment,
        ));

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
