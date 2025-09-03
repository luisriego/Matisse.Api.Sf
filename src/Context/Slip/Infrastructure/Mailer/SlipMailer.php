<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Mailer;

use App\Context\Slip\Application\Dto\SlipEmailDto;
use App\Context\Slip\Application\Service\SlipMailerInterface;
use DateTimeImmutable;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

use function number_format;
use function sprintf;

final readonly class SlipMailer implements SlipMailerInterface
{
    public function __construct(private MailerInterface $mailer) {}

    /**
     * @throws TransportExceptionInterface
     */
    public function sendSlipSubmittedEmail(SlipEmailDto $slipData): void
    {
        $recipients = $slipData->recipients();

        if (empty($recipients)) {
            return;
        }

        // Convertir el string de fecha a un objeto DateTime para poder formatearlo de forma segura.
        // Esto soluciona el bug principal causado por la deserialización del mensaje.
        $dueDate = new DateTimeImmutable($slipData->dueDate());

        $subject = sprintf(
            'Novo boleto para a unidade %s',
            $slipData->unitNumber(),
        );

        // Helper para obtener el nombre del mes en portugués
        $monthsInPortuguese = [
            1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
            5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
            9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
        ];
        $monthName = $monthsInPortuguese[(int) $dueDate->format('n')] ?? 'mês desconhecido';

        foreach ($recipients as $recipient) {
            $recipientEmail = $recipient['email'] ?? null;
            $recipientName = $recipient['name'] ?? 'Prezado(a)';

            if (null === $recipientEmail) {
                continue;
            }

            $body = sprintf(
                "Olá, %s\n\nFoi gerado o boleto do mês de %s:\n\nPelo valor de: R$%s\ncom vencimento: %s\n\nObrigado.",
                $recipientName,
                $monthName,
                number_format($slipData->amount() / 100, 2, '.', ''),
                $dueDate->format('d-m-Y'),
            );

            $email = (new Email())
                ->from('noreply@expresate.com')
                ->to($recipientEmail)
                ->subject($subject)
                ->text($body);

            $this->mailer->send($email);
        }
    }
}
