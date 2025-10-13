<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

final class UnwrapHandlerFailedExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HandlerFailedException) {
            $wrapped = $exception->getWrappedExceptions()[0] ?? null;

            if ($wrapped !== null) {
                $event->setThrowable($wrapped);
            }
        }
    }
}
