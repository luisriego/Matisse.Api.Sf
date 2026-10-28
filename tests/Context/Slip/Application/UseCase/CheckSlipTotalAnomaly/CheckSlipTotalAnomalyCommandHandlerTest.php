<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Application\UseCase\CheckSlipTotalAnomaly;

use App\Context\ResidentUnit\Application\Message\WelcomeResidentNotification;
use App\Context\ResidentUnit\Application\Message\WelcomeResidentNotificationHandler;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Slip\Application\Message\SendSlipNotification;
use App\Context\Slip\Application\Message\SendSlipNotificationHandler;
use App\Context\Slip\Application\Service\SlipAlertService;
use App\Context\Slip\Application\UseCase\CheckSlipTotalAnomaly\CheckSlipTotalAnomalyCommand;
use App\Context\Slip\Application\UseCase\CheckSlipTotalAnomaly\CheckSlipTotalAnomalyCommandHandler;
use App\Context\Slip\Domain\ValueObject\SlipAmount;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class CheckSlipTotalAnomalyCommandHandlerTest extends TestCase
{
    private MockObject&SlipAlertService $slipAlertService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->slipAlertService = $this->createMock(SlipAlertService::class);
    }

    #[Test]
    public function testReturnsStdClassAlertWhenAnomalyIsDetectedWithoutTypeError(): void
    {
        // Arrange
        $expectedAlert = new \stdClass();
        $expectedAlert->titulo = 'Alerta: Total de Slip Anormal';
        $expectedAlert->diagnostico = 'El monto está fuera del rango esperado.';
        $expectedAlert->recomendacao = 'Revise el slip.';

        $this->slipAlertService
            ->method('checkAndGenerateAnomalyAlert')
            ->willReturn($expectedAlert);

        $handler = new CheckSlipTotalAnomalyCommandHandler(
            $this->slipAlertService,
        );

        $command = new CheckSlipTotalAnomalyCommand(
            new SlipAmount(100000), // Below minimum threshold
        );

        // Act
        $result = ($handler)($command);

        // Assert: debe devolver stdClass sin lanzar TypeError
        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame('Alerta: Total de Slip Anormal', $result->titulo);
        $this->assertSame('El monto está fuera del rango esperado.', $result->diagnostico);
        $this->assertSame('Revise el slip.', $result->recomendacao);
    }

    #[Test]
    public function testReturnsNullWhenAmountIsWithinNormalRange(): void
    {
        // Arrange
        $this->slipAlertService
            ->method('checkAndGenerateAnomalyAlert')
            ->willReturn(null);

        $handler = new CheckSlipTotalAnomalyCommandHandler(
            $this->slipAlertService,
        );

        $command = new CheckSlipTotalAnomalyCommand(
            new SlipAmount(750000), // Within normal range (500000-1000000)
        );

        // Act
        $result = ($handler)($command);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function testWelcomeResidentNotificationHandlerDoesNotEchoToStdout(): void
    {
        // Arrange
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerFrom = 'noreply@matisse.com';

        $handler = new WelcomeResidentNotificationHandler(
            $mailerMock,
            $mailerFrom,
        );

        $message = new WelcomeResidentNotification(
            name: 'Juan Pérez',
            email: 'juan@example.com',
            unitName: 'Apto 101',
        );

        // Act: capture stdout
        ob_start();
        ($handler)($message);
        $output = ob_get_clean();

        // Assert: no debe haber salida por stdout
        $this->assertSame('', $output);
    }

    #[Test]
    public function testSendSlipNotificationHandlerDoesNotSendToTestEmailWhenNoRecipients(): void
    {
        // Arrange
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerFrom = 'noreply@matisse.com';
        $residentUnitRepo = $this->createMock(ResidentUnitRepository::class);

        // Simulate: no recipients found for the unit
        $residentUnitRepo
            ->method('findOneById')
            ->willReturn(null);

        $handler = new SendSlipNotificationHandler(
            $mailerMock,
            $mailerFrom,
            $residentUnitRepo,
        );

        // Expect: mailer->send() is NEVER called (no test@example.com)
        $mailerMock->expects($this->never())
            ->method('send');

        $message = new SendSlipNotification(
            slipId: 'slip-123',
            residentUnitId: 'unit-456',
            amount: 500000,
            dueDate: '2025-01-15',
        );

        // Act
        ($handler)($message);
    }

    #[Test]
    public function testSendSlipNotificationHandlerSendsToRealRecipientWhenFound(): void
    {
        // Arrange
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerFrom = 'noreply@matisse.com';
        $residentUnitRepo = $this->createMock(ResidentUnitRepository::class);

        // Simulate: recipients found for the unit
        $realRecipient = ['name' => 'Juan Pérez', 'email' => 'juan@real.com'];
        $unitMock = $this->createMock(\App\Context\ResidentUnit\Domain\ResidentUnit::class);
        $unitMock
            ->method('notificationRecipients')
            ->willReturn([$realRecipient]);

        $residentUnitRepo
            ->method('findOneById')
            ->willReturn($unitMock);

        // Expect: mailer->send() is called exactly once with the real recipient
        $emailSent = null;
        $mailerMock->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (Email $email) use (&$emailSent) {
                $emailSent = $email;
            });

        $handler = new SendSlipNotificationHandler(
            $mailerMock,
            $mailerFrom,
            $residentUnitRepo,
        );

        $message = new SendSlipNotification(
            slipId: 'slip-123',
            residentUnitId: 'unit-456',
            amount: 500000,
            dueDate: '2025-01-15',
        );

        // Act
        ($handler)($message);

        // Assert
        $this->assertNotNull($emailSent);
        $toAddresses = $emailSent->getTo();
        $this->assertCount(1, $toAddresses);
        // Symfony Email::getTo() returns array of Address objects
        $address = $toAddresses[0];
        $this->assertSame('juan@real.com', $address->getAddress());
    }
}
