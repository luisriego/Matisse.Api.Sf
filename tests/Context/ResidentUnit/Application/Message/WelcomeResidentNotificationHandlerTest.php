<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Application\Message;

use App\Context\ResidentUnit\Application\Message\WelcomeResidentNotification;
use App\Context\ResidentUnit\Application\Message\WelcomeResidentNotificationHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

use function sprintf;

class WelcomeResidentNotificationHandlerTest extends TestCase
{
    private const string MAILER_FROM = 'no-reply@matisse.test';

    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private WelcomeResidentNotificationHandler $handler;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = new WelcomeResidentNotificationHandler($this->mailer, self::MAILER_FROM, $this->logger);
    }

    public function testItShouldSendWelcomeEmail(): void
    {
        $name = 'John Doe';
        $email = 'john.doe@example.com';
        $unitName = 'Unit 101';

        $message = new WelcomeResidentNotification($name, $email, $unitName);

        $this->mailer->expects($this->once())
            ->method('send')
            ->with(self::callback(function (Email $sentEmail) use ($name, $email) {
                $this->assertSame(self::MAILER_FROM, $sentEmail->getFrom()[0]->getAddress());
                $this->assertSame($email, $sentEmail->getTo()[0]->getAddress());
                $this->assertSame(sprintf('Boas-vindas ao seu novo lar, %s!', $name), $sentEmail->getSubject());
                $this->assertStringContainsString(sprintf('Oi %s,\n\nSeja bem-vindo(a) ao Condomínio Matisse.', $name), $sentEmail->getTextBody());
                $this->assertStringContainsString(sprintf('<p>Oi %s,</p><p>Seja bem-vindo(a) ao Condomínio Matisse.', $name), $sentEmail->getHtmlBody());

                return true;
            }));

        $this->logger->expects($this->once())
            ->method('info')
            ->with(sprintf('[OK] E-mail de boas-vindas para %s (%s) enviado para %s.', $name, $unitName, $email));

        ($this->handler)($message);
    }

    public function testItShouldPropagateTransportException(): void
    {
        $name = 'John Doe';
        $email = 'john.doe@example.com';
        $unitName = 'Unit 101';

        $message = new WelcomeResidentNotification($name, $email, $unitName);

        $this->mailer->expects($this->once())
            ->method('send')
            ->willThrowException($this->createStub(TransportExceptionInterface::class));

        $this->expectException(TransportExceptionInterface::class);

        ($this->handler)($message);
    }
}
