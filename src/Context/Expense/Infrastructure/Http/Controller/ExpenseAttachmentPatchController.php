<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Http\Controller;

use App\Context\Expense\Application\UseCase\AddExpenseAttachment\AddExpenseAttachmentCommand;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Infrastructure\Symfony\ApiController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

final class ExpenseAttachmentPatchController extends ApiController
{
    public function __invoke(Request $request, string $id): JsonResponse
    {
        $attachment = $request->files->get('attachment');

        if (!$attachment instanceof UploadedFile) {
            throw new InvalidArgumentException('Anexo inválido');
        }

        try {
            $this->dispatch(new AddExpenseAttachmentCommand(
                expenseId: $id,
                attachment: $attachment,
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
