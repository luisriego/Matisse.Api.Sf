<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Application\UseCase\CreateUnitWithRecipients;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class TestMessageBus implements MessageBusInterface
{
    public int $dispatchCallCount = 0;
    public array $dispatchedMessages = [];

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $this->dispatchCallCount++;
        $this->dispatchedMessages[] = $message;

        // Return a dummy Envelope, as the actual return value is not used by the handler
        return new Envelope($message);
    }
}
