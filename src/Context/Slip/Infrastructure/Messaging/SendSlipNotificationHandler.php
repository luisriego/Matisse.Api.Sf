<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Messaging;

use App\Context\Slip\Application\Message\SendSlipNotification;

// ATRIBUTO #[AsMessageHandler] ELIMINADO PARA EVITAR DUPLICIDAD.
// El manejador activo está en App\Context\Slip\Application\Message\SendSlipNotificationHandler.
final readonly class SendSlipNotificationHandler
{
    public function __invoke(SendSlipNotification $message): void
    {
        // Este handler ha sido desactivado.
        // TODO: Integrar mailer/servicio de notificaciones en el handler de Application.
    }
}
