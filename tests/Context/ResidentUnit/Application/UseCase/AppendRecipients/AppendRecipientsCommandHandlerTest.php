<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Application\UseCase\AppendRecipients;

use App\Context\ResidentUnit\Application\UseCase\AppendRecipients\AppendRecipientsCommand;
use App\Context\ResidentUnit\Application\UseCase\AppendRecipients\AppendRecipientsCommandHandler;
use App\Context\ResidentUnit\Domain\Exception\ResidentUnitNotFoundException;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitIdMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Shared\Infrastructure\PhpUnit\UnitTestCase;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

final class AppendRecipientsCommandHandlerTest extends UnitTestCase
{
    private AppendRecipientsCommandHandler $handler;
    private MockObject|ResidentUnitRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(ResidentUnitRepository::class);
        $this->handler = new AppendRecipientsCommandHandler($this->repository);
    }

    public function testItShouldAppendARecipientToAResidentUnit(): void
    {
        // Arrange
        $residentUnit = ResidentUnitMother::create();
        $command = new AppendRecipientsCommand(
            $residentUnit->id(),
            'Test Name',
            'test@example.com',
        );

        $this->repository->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($residentUnit->id())
            ->willReturn($residentUnit);

        $this->repository->expects(self::once())
            ->method('save')
            ->with($residentUnit);

        // Act
        ($this->handler)($command);
    }

    public function testItShouldThrowAnExceptionWhenResidentUnitDoesNotExist(): void
    {
        // Assert
        $this->expectException(ResidentUnitNotFoundException::class);

        // Arrange
        $id = ResidentUnitIdMother::create()->value();
        $command = new AppendRecipientsCommand(
            $id,
            'Test Name',
            'test@example.com',
        );

        $this->repository->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($id)
            ->willThrowException(new ResidentUnitNotFoundException());

        // Act
        ($this->handler)($command);
    }

    public function testItShouldAddARecipientWhenOthersAlreadyExist(): void
    {
        // Arrange
        $initialRecipient = ['name' => 'Ana', 'email' => 'ana@example.com'];
        $residentUnit = ResidentUnitMother::create();
        $residentUnit->replaceRecipients([$initialRecipient]);

        $newRecipientName = 'Carlos';
        $newRecipientEmail = 'carlos@example.com';
        $command = new AppendRecipientsCommand(
            $residentUnit->id(),
            $newRecipientName,
            $newRecipientEmail,
        );

        $this->repository->expects(self::once())
            ->method('findOneByIdOrFail')
            ->willReturn($residentUnit);

        $this->repository->expects(self::once())
            ->method('save')
            ->with($this->callback(function ($savedUnit) use ($initialRecipient, $newRecipientEmail) {
                $recipients = $savedUnit->notificationRecipients();

                self::assertCount(2, $recipients);

                self::assertSame($initialRecipient['email'], $recipients[0]['email']);

                self::assertSame($newRecipientEmail, $recipients[1]['email']);

                return true;
            }));

        // Act
        ($this->handler)($command);
    }

    public function testItShouldPropagateExceptionIfSaveFails(): void
    {
        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Database is down');

        // Arrange
        $residentUnit = ResidentUnitMother::create();
        $command = new AppendRecipientsCommand(
            $residentUnit->id(),
            'Test Name',
            'test@example.com',
        );

        $this->repository->expects(self::once())
            ->method('findOneByIdOrFail')
            ->willReturn($residentUnit);

        $this->repository->expects(self::once())
            ->method('save')
            ->willThrowException(new Exception('Database is down'));

        // Act
        ($this->handler)($command);
    }
}
