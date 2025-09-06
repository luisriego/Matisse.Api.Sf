<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Application\Message;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

use function sprintf;

#[AsMessageHandler]
final readonly class WelcomeResidentNotificationHandler
{
    public function __construct(private MailerInterface $mailer) {}

    /**
     * @throws TransportExceptionInterface
     */
    public function __invoke(WelcomeResidentNotification $message): void
    {
        $email = (new Email())
            ->from('no-reply@expresate.com')
            ->to($message->email)
            ->subject(sprintf('Boas-vindas ao seu novo lar, %s!', $message->name))
            ->text(sprintf(
                'Oi %s,\n\nSeja bem-vindo(a) ao Condomínio Matisse. Estamos felizes em ter você com a gente!\n\nAbraços,\nA Equipe da Administração',
                $message->name,
            ))
            ->html(sprintf(
                '<p>Oi %s,</p><p>Seja bem-vindo(a) ao Condomínio Matisse. Estamos felizes em ter você com a gente!</p><p>Abraços,<br>A Equipe da Administração</p>',
                $message->name,
            ));

        $this->mailer->send($email);

        echo sprintf(
            "\n[OK] E-mail de boas-vindas para %s (%s) enviado para %s.\n",
            $message->name,
            $message->unitName,
            $message->email,
        );
    }
}
