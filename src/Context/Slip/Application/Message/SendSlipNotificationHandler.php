<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\Message;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendSlipNotificationHandler
{
    public function __invoke(SendSlipNotification $message): void
    {
        dump('¡Handler invocado! Procesando mensaje:', $message);

        // Aquí iría la lógica para enviar el email, por ejemplo:
        // $this->mailer->send(...);

        echo sprintf(
            '[OK] Email para el Slip %s enviado (simulado).', 
            $message->aggregateId()
        );
    }
}
