<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\Message;

use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

use function array_values;
use function sprintf;

#[AsMessageHandler]
final readonly class SendSlipNotificationHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private string $mailerFrom,
        private ?ResidentUnitRepository $residentUnitRepository = null,
    ) {}

    public function __invoke(SendSlipNotification $message): void
    {
        // Resolve real recipients from the resident unit
        if (null === $this->residentUnitRepository) {
            return;
        }

        $residentUnit = $this->residentUnitRepository->findOneById($message->residentUnitId);

        if (null === $residentUnit) {
            return;
        }

        $recipients = $residentUnit->notificationRecipients();

        if (empty($recipients)) {
            return;
        }

        // Build the list of recipient emails
        $recipientEmails = [];

        foreach ($recipients as $recipient) {
            if (isset($recipient['email'])) {
                $recipientEmails[$recipient['email']] = $recipient['email'];
            }
        }

        if (empty($recipientEmails)) {
            return;
        }

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to(array_values($recipientEmails)[0]) // Send to first recipient
            ->subject('Nuevo recibo de pago generado')
            ->text(sprintf(
                'Se ha generado un nuevo recibo de pago para usted.\nID del Recibo: %s\nMonto: %.2f\nFecha de Vencimiento: %s',
                $message->slipId,
                $message->amount / 100,
                $message->dueDate,
            ))
            ->html(sprintf(
                '<p>Se ha generado un nuevo recibo de pago para usted.</p><ul><li>ID del Recibo: %s</li><li>Monto: %.2f</li><li>Fecha de Vencimiento: %s</li></ul>',
                $message->slipId,
                $message->amount / 100,
                $message->dueDate,
            ));

        $this->mailer->send($email);
    }
}
