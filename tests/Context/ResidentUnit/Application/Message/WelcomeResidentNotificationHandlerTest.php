<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Application\Message;

use App\Context\ResidentUnit\Application\Message\WelcomeResidentNotification;
use App\Context\ResidentUnit\Application\Message\WelcomeResidentNotificationHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

use function ob_get_clean;
use function ob_start;
use function sprintf;

class WelcomeResidentNotificationHandlerTest extends TestCase
{
    private const string MAILER_FROM = 'no-reply@matisse.test';

    private MailerInterface $mailer;
    private WelcomeResidentNotificationHandler $handler;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->handler = new WelcomeResidentNotificationHandler($this->mailer, self::MAILER_FROM);
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

        // Capture output to avoid it polluting test results
        ob_start();

        try {
            ($this->handler)($message);
        } finally {
            $output = ob_get_clean();
        }

        $this->assertStringContainsString(sprintf("[OK] E-mail de boas-vindas para %s (%s) enviado para %s.\n", $name, $unitName, $email), $output);
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

        // Capture output to ensure nothing is printed before the exception
        ob_start();

        try {
            ($this->handler)($message);
        } finally {
            $output = ob_get_clean();
        }

        $this->assertEmpty($output); // No output should be printed
    }
}
