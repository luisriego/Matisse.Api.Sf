<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Messaging;

use App\Context\Slip\Application\Message\SendSlipNotification;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SendSlipNotificationHandler
{
    public function __invoke(SendSlipNotification $message): void
    {
        // Aquí deberías invocar tu servicio real de envío de emails.
        // Este handler existe para consumir el mensaje y evitar el "No handler for message".
        // TODO: Integrar mailer/servicio de notificaciones.
    }
}
