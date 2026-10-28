<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function array_key_exists;
use function is_array;

final class ApiControllerExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 150],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $controller = $event->getRequest()->attributes->get('_controller');

        // Resolve the ApiController instance from the controller attribute.
        // Supports both invokable objects and array tuples [controller, method].
        if ($controller instanceof ApiController) {
            $apiController = $controller;
        } elseif (is_array($controller) && ($controller[0] ?? null) instanceof ApiController) {
            $apiController = $controller[0];
        } else {
            return;
        }

        $exceptionsMap = $apiController->exceptions();
        $exceptionClass = $exception::class;

        if (!array_key_exists($exceptionClass, $exceptionsMap)) {
            return;
        }

        $statusCode = $exceptionsMap[$exceptionClass];
        $response = new JsonResponse(
            [
                'class' => $exceptionClass,
                'code' => $statusCode,
                'message' => $exception->getMessage(),
            ],
            $statusCode,
        );

        $event->setResponse($response);
    }
}
