<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\Message;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

use function sprintf;

#[AsMessageHandler]
final readonly class SendSlipNotificationHandler
{
    public function __construct(private MailerInterface $mailer) {}

    public function __invoke(SendSlipNotification $message): void
    {
        // TODO: En un futuro, obtener el email del residente desde la base de datos
        // usando el $message->residentUnitId.
        $recipientEmail = 'test@example.com';

        $email = (new Email())
            ->from('no-reply@example.com')
            ->to($recipientEmail)
            ->subject('Nuevo recibo de pago generado')
            ->text(sprintf(
                'Se ha generado un nuevo recibo de pago para usted.\nID del Recibo: %s\nMonto: %.2f\nFecha de Vencimiento: %s',
                $message->slipId,
                $message->amount / 100, // Asumiendo que el monto está en céntimos
                $message->dueDate,
            ))
            ->html(sprintf(
                '<p>Se ha generado un nuevo recibo de pago para usted.</p><ul><li>ID del Recibo: %s</li><li>Monto: %.2f</li><li>Fecha de Vencimiento: %s</li></ul>',
                $message->slipId,
                $message->amount / 100, // Asumiendo que el monto está en céntimos
                $message->dueDate,
            ));

        $this->mailer->send($email);

        echo sprintf(
            "\n[OK] Email para el Slip %s enviado a %s a través de Mailtrap.\n",
            $message->slipId,
            $recipientEmail,
        );
    }
}
