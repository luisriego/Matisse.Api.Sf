<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Mailer;

use App\Context\User\Application\Service\UserMailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

use function sprintf;

final class UserMailer implements UserMailerInterface
{
    public function __construct(private readonly MailerInterface $mailer) {}

    /**
     * @throws TransportExceptionInterface
     */
    public function sendWelcomeEmail(string $userEmail, string $userName): void
    {
        $email = (new Email())
            ->from('no-reply@example.com') // <--- Configura tu dirección de remitente
            ->to($userEmail)
            ->subject('¡Bienvenido a nuestra aplicación!')
            ->html(sprintf('<p>Hola %s,</p><p>¡Gracias por registrarte!</p>', $userName));

        $this->mailer->send($email);
    }
}
