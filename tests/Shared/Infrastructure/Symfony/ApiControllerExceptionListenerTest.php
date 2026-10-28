<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Symfony;

use App\Shared\Domain\Exception\InvalidDataException;
use App\Shared\Infrastructure\JsonTransformerExceptionListener;
use App\Shared\Infrastructure\Symfony\ApiController;
use App\Shared\Infrastructure\Symfony\ApiControllerExceptionListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

use function json_decode;

#[CoversClass(ApiControllerExceptionListener::class)]
#[CoversClass(JsonTransformerExceptionListener::class)]
final class ApiControllerExceptionListenerTest extends TestCase
{
    public function testInvokableApiControllerMapsCustomExceptionToConfiguredStatusCode(): void
    {
        $controller = $this->createApiController([
            RuntimeException::class => Response::HTTP_CONFLICT,
        ]);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->attributes->set('_controller', $controller);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new RuntimeException('Test exception'),
        );

        $listener = new ApiControllerExceptionListener();
        $listener->onKernelException($event);

        self::assertInstanceOf(JsonResponse::class, $event->getResponse());
        self::assertSame(Response::HTTP_CONFLICT, $event->getResponse()->getStatusCode());

        $data = json_decode($event->getResponse()->getContent(), true);
        self::assertSame(RuntimeException::class, $data['class']);
        self::assertSame(Response::HTTP_CONFLICT, $data['code']);
        self::assertSame('Test exception', $data['message']);
    }

    public function testArrayTupleApiControllerMapsCustomExceptionToConfiguredStatusCode(): void
    {
        $controller = $this->createApiController([
            RuntimeException::class => Response::HTTP_CONFLICT,
        ]);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $request->attributes->set('_controller', [$controller, '__invoke']);

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new RuntimeException('Test exception'),
        );

        $listener = new ApiControllerExceptionListener();
        $listener->onKernelException($event);

        self::assertInstanceOf(JsonResponse::class, $event->getResponse());
        self::assertSame(Response::HTTP_CONFLICT, $event->getResponse()->getStatusCode());

        $data = json_decode($event->getResponse()->getContent(), true);
        self::assertSame(RuntimeException::class, $data['class']);
        self::assertSame(Response::HTTP_CONFLICT, $data['code']);
    }

    public function testSubscribedEventsRegistersPriority150AboveJsonTransformer(): void
    {
        $subscribedEvents = ApiControllerExceptionListener::getSubscribedEvents();

        self::assertArrayHasKey(KernelEvents::EXCEPTION, $subscribedEvents);
        self::assertSame(['onKernelException', 150], $subscribedEvents[KernelEvents::EXCEPTION]);
    }

    public function testJsonTransformerExceptionListenerMapsInvalidDataExceptionTo422(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();

        $event = new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new InvalidDataException('Invalid data'),
        );

        $listener = new JsonTransformerExceptionListener();
        $listener->onKernelException($event);

        self::assertInstanceOf(JsonResponse::class, $event->getResponse());
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $event->getResponse()->getStatusCode());

        $data = json_decode($event->getResponse()->getContent(), true);
        self::assertSame(InvalidDataException::class, $data['class']);
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $data['code']);
    }

    private function createApiController(array $exceptionsMap): ApiController
    {
        return new class(new class implements MessageBusInterface {
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                return new Envelope($message);
            }
        }, new class implements MessageBusInterface {
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                return new Envelope($message);
            }
        }, $exceptionsMap) extends ApiController {
            private array $exceptionsMap;

            public function __construct(MessageBusInterface $commandBus, MessageBusInterface $queryBus, array $exceptionsMap)
            {
                parent::__construct($commandBus, $queryBus);
                $this->exceptionsMap = $exceptionsMap;
            }

            public function exceptions(): array
            {
                return $this->exceptionsMap;
            }
        };
    }
}
