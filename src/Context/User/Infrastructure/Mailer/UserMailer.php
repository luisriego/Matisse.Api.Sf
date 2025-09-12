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
    public function sendConfirmationEmail(string $userEmail, string $userName, string $userId, string $confirmationToken): void
    {
        // TODO: The base URL should come from a configuration parameter, not be hardcoded.
        $activationUrl = sprintf('http://localhost:1000/api/v1/users/activate/%s/%s', $userId, $confirmationToken);

        $email = (new Email())
            ->from('no-reply@example.com')
            ->to($userEmail)
            ->subject('Confirma tu cuenta')
            ->html(sprintf(
                '<p>Hola %s,</p><p>Gracias por registrarte. Por favor, confirma tu cuenta haciendo clic en el siguiente enlace:</p><p><a href="%s">Confirmar cuenta</a></p>',
                $userName,
                $activationUrl
            ));

        $this->mailer->send($email);
    }
}
