<?php

declare(strict_types=1);

namespace App\Context\User\Infrastructure\Mailer;

use App\Context\User\Application\Service\UserMailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

use function rtrim;
use function sprintf;

final class UserMailer implements UserMailerInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $mailerFrom,
        private readonly string $appBaseUrl,
        private readonly string $frontSetPasswordPath,
    ) {}

    /**
     * @throws TransportExceptionInterface
     */
    public function sendConfirmationEmail(string $userEmail, string $userName, string $userId, string $confirmationToken): void
    {
        $activationUrl = sprintf('%s/api/v1/users/activate/%s/%s', $this->appBaseUrl, $userId, $confirmationToken);

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($userEmail)
            ->subject('Confirma tu cuenta')
            ->html(sprintf(
                '<p>Hola %s,</p><p>Gracias por registrarte. Por favor, confirma tu cuenta haciendo clic en el siguiente enlace:</p><p><a href="%s">Confirmar cuenta</a></p>',
                $userName,
                $activationUrl,
            ));

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendPasswordResetEmail(string $userEmail, string $userName, string $userId, string $passwordResetToken): void
    {
        $resetUrl = sprintf(
            '%s/%s/%s/%s',
            rtrim($this->appBaseUrl, '/'),
            trim($this->frontSetPasswordPath, '/'),
            $userId,
            $passwordResetToken,
        );

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to($userEmail)
            ->subject('Redefinir sua senha')
            ->html(sprintf(
                '<p>Olá %s,</p><p>Você solicitou a redefinição de senha. Clique no link abaixo para continuar:</p><p><a href="%s">Redefinir senha</a></p>',
                $userName,
                $resetUrl,
            ));

        $this->mailer->send($email);
    }
}
