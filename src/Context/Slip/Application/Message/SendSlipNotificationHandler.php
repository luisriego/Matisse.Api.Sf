<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\Message;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendSlipNotificationHandler
{
    public function __invoke(SendSlipNotification $message): void
    {
        // La lógica de envío de email iría aquí.
        // Por ahora, solo simulamos y mostramos en consola.

        echo sprintf(
            "\n[OK] Simulación de envío de email para el Slip: %s\n",
            $message->slipId
        );
    }
}
