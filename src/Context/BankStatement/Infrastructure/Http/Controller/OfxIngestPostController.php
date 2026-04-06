<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Http\Controller;

use App\Context\BankStatement\Application\UseCase\PreviewBankStatement\PreviewBankStatementQuery;
use App\Shared\Infrastructure\Symfony\ApiController;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * First step of OFX ingestion: parse file, match history, return lines for review (no persistence).
 */
final class OfxIngestPostController extends ApiController
{
    public function __construct(
        MessageBusInterface $commandBus,
        MessageBusInterface $queryBus,
        private readonly NormalizerInterface $normalizer,
    ) {
        parent::__construct($commandBus, $queryBus);
    }

    public function __invoke(Request $request): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile) {
            return new JsonResponse(
                ['error' => 'Se requiere un archivo OFX (multipart/form-data, campo "file").'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $ofxContent = (string) file_get_contents($file->getPathname());

        if ($ofxContent === '') {
            return new JsonResponse(['error' => 'El archivo OFX está vacío.'], Response::HTTP_BAD_REQUEST);
        }

        $preview = $this->ask(new PreviewBankStatementQuery($ofxContent));

        return new JsonResponse(
            $this->normalizer->normalize($preview),
            Response::HTTP_OK,
        );
    }

    public function exceptions(): array
    {
        return [
            RuntimeException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
