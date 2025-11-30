<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use App\Context\Gas\Domain\Exception\GasPriceNotFoundException;
use App\Context\Gas\Domain\Exception\GasReadingNotFoundException;
use App\Context\ResidentUnit\Domain\Exception\IdealFractionSumExceedsLimitException;
use App\Context\ResidentUnit\Domain\Exception\ResidentUnitAlreadyExistsException; // Added this import
use App\Shared\Domain\Exception\AccessDeniedException;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Shared\Domain\Exception\ResourceAlreadyExistException;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class JsonTransformerExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $e = $event->getThrowable();

        $data = [
            'class' => $e::class,
            'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $e->getMessage(),
        ];

        if ($e instanceof ResourceAlreadyExistException) {
            $data['code'] = Response::HTTP_BAD_REQUEST;
        }

        if ($e instanceof ResidentUnitAlreadyExistsException) { // Added this condition
            $data['code'] = Response::HTTP_CONFLICT;
        }

        if ($e instanceof ResourceNotFoundException || $e instanceof GasPriceNotFoundException || $e instanceof GasReadingNotFoundException) {
            $data['code'] = Response::HTTP_NOT_FOUND;
        }

        if ($e instanceof InvalidArgumentException || $e instanceof JsonException) {
            $data['code'] = Response::HTTP_BAD_REQUEST;
        }

        if ($e instanceof AccessDeniedException) {
            $data['code'] = Response::HTTP_FORBIDDEN;
        }

        if ($e instanceof IdealFractionSumExceedsLimitException) {
            $data['code'] = Response::HTTP_CONFLICT;
        }

        $response = new JsonResponse($data, $data['code']);

        $event->setResponse($response);
    }
}
