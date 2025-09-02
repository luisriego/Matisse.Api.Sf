<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Mailer;

use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Slip\Application\Dto\SlipEmailDto;
use App\Context\Slip\Application\Service\SlipMailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final readonly class SlipMailer implements SlipMailerInterface
{
    public function __construct(private MailerInterface $mailer)
    {}

    /**
     * @throws TransportExceptionInterface
     */
    public function sendSlipSubmittedEmail(SlipEmailDto $slipData): void
    {
        $recipients = $slipData->recipients();

        if (empty($recipients)) {
            return;
        }

        $subject = sprintf(
            'Novo boleto para a unidade %s',
            $slipData->unitNumber()
        );

        // Helper para obtener el nombre del mes en portugués
        $monthsInPortuguese = [
            1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
            5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
            9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
        ];
        $monthName = $monthsInPortuguese[(int)$slipData->monthDueDate()] ?? 'mes desconhecido';

        foreach ($recipients as $recipient) {
            $recipientEmail = $recipient['email'] ?? null;
            $recipientName = $recipient['name'] ?? 'Prezado(a)'; // Nombre por defecto si no existe

            if (null === $recipientEmail) {
                continue;
            }

            // Generar el cuerpo del email personalizado para cada destinatario
            $body = sprintf(
                "Prezado(a), %s\n\nFoi gerado o boleto do mês de %s:\n\nPelo valor de: R$%s\ncom vencimento: %s\n\nObrigado.",
                $recipientName,
                $monthName,
                number_format($slipData->amount() / 100, 2, '.', ''),
                $slipData->dueDate()
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
