<?php

declare(strict_types=1);

namespace App\Tests\Context\ResidentUnit\Application\UseCase\CreateUnitWithRecipients;

use App\Context\ResidentUnit\Application\Message\WelcomeResidentNotification;
use App\Context\ResidentUnit\Application\UseCase\CreateUnitWithRecipients\CreateResidentUnitWithRecipientsCommand;
use App\Context\ResidentUnit\Application\UseCase\CreateUnitWithRecipients\CreateResidentUnitWithRecipientsCommandHandler;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Shared\Domain\Exception\InvalidArgumentException;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitIdealFractionMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitIdMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitVOMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Context\ResidentUnit\Domain\ResidentUnitId;
use App\Context\ResidentUnit\Domain\ResidentUnitIdealFraction;

class CreateResidentUnitWithRecipientsCommandHandlerTest extends TestCase
{
    private CreateResidentUnitWithRecipientsCommandHandler $handler;
    private ResidentUnitRepository $repository;
    private TestMessageBus $messageBus; // Changed to use TestMessageBus

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ResidentUnitRepository::class);
        $this->messageBus = new TestMessageBus(); // Instantiated TestMessageBus
        $this->handler = new CreateResidentUnitWithRecipientsCommandHandler(
            $this->repository,
            $this->messageBus
        );
    }

    public function test_it_should_create_a_resident_unit_with_recipients(): void
    {
        $id = ResidentUnitIdMother::create();
        $unit = ResidentUnitVOMother::create();
        $idealFraction = ResidentUnitIdealFractionMother::create(0.1); // Fixed small value
        $recipients = [
            ['name' => 'John Doe', 'email' => 'john.doe@example.com'],
            ['name' => 'Jane Doe', 'email' => 'jane.doe@example.com'],
        ];

        $command = new CreateResidentUnitWithRecipientsCommand(
            $id->value(),
            $unit->value(),
            $idealFraction->value(),
            $recipients
        );

        $this->repository->expects($this->once())
            ->method('calculateTotalIdealFraction')
            ->willReturn(0.5); // Ensure total ideal fraction is less than 1.0

        $this->repository->expects($this->once())
            ->method('save')
            ->with(self::isInstanceOf(ResidentUnit::class), true);

        // Assertions for TestMessageBus
        $this->messageBus->dispatchCallCount = 0; // Reset for this test
        ($this->handler)($command);
        $this->assertSame(count($recipients), $this->messageBus->dispatchCallCount);
        foreach ($this->messageBus->dispatchedMessages as $dispatchedMessage) {
            $this->assertInstanceOf(WelcomeResidentNotification::class, $dispatchedMessage);
        }
    }

    public function test_it_should_throw_exception_if_ideal_fraction_exceeds_one(): void
    {
        $id = ResidentUnitIdMother::create();
        $unit = ResidentUnitVOMother::create();
        $idealFraction = ResidentUnitIdealFractionMother::create(0.8); // Fixed value to exceed 1.0
        $recipients = [];

        $command = new CreateResidentUnitWithRecipientsCommand(
            $id->value(),
            $unit->value(),
            $idealFraction->value(),
            $recipients
        );

        $this->repository->expects($this->once())
            ->method('calculateTotalIdealFraction')
            ->willReturn(0.3); // Make it exceed 1.0 when adding idealFraction

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ideal fraction must not be more than 1');

        // Ensure save and dispatch are not called
        $this->repository->expects($this->never())->method('save');
        $this->messageBus->dispatchCallCount = 0; // Reset for this test
        ($this->handler)($command);
        $this->assertSame(0, $this->messageBus->dispatchCallCount);
    }

    public function test_it_should_create_a_resident_unit_without_recipients(): void
    {
        $id = ResidentUnitIdMother::create();
        $unit = ResidentUnitVOMother::create();
        $idealFraction = ResidentUnitIdealFractionMother::create(0.1); // Fixed small value
        $recipients = [];

        $command = new CreateResidentUnitWithRecipientsCommand(
            $id->value(),
            $unit->value(),
            $idealFraction->value(),
            $recipients
        );

        $this->repository->expects($this->once())
            ->method('calculateTotalIdealFraction')
            ->willReturn(0.5); // Ensure total ideal fraction is less than 1.0

        $this->repository->expects($this->once())
            ->method('save')
            ->with(self::isInstanceOf(ResidentUnit::class), true);

        // Ensure dispatch is not called when there are no recipients
        $this->messageBus->dispatchCallCount = 0; // Reset for this test
        ($this->handler)($command);
        $this->assertSame(0, $this->messageBus->dispatchCallCount);
    }

    public function test_it_should_throw_exception_if_id_is_invalid(): void
    {
        $invalidId = 'not-a-valid-uuid';
        $unit = ResidentUnitVOMother::create();
        $idealFraction = ResidentUnitIdealFractionMother::create();
        $recipients = [];

        $command = new CreateResidentUnitWithRecipientsCommand(
            $invalidId,
            $unit->value(),
            $idealFraction->value(),
            $recipients
        );

        // Expect calculateTotalIdealFraction to be called, as the ID validation happens after this
        $this->repository->expects($this->once())
            ->method('calculateTotalIdealFraction')
            ->willReturn(0.0); // Return a dummy value

        // Ensure save and dispatch are not called
        $this->repository->expects($this->never())->method('save');
        $this->messageBus->dispatchCallCount = 0; // Reset for this test

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('<%s> does not allow the value <%s>.', ResidentUnitId::class, $invalidId));

        ($this->handler)($command);

        $this->assertSame(0, $this->messageBus->dispatchCallCount);
    }

    public function test_it_should_throw_exception_if_ideal_fraction_is_not_positive(): void
    {
        $id = ResidentUnitIdMother::create();
        $unit = ResidentUnitVOMother::create();
        $invalidIdealFraction = -0.1; // Test with negative or zero
        $recipients = [];

        $command = new CreateResidentUnitWithRecipientsCommand(
            $id->value(),
            $unit->value(),
            $invalidIdealFraction,
            $recipients
        );

        // Expect calculateTotalIdealFraction to NOT be called, as the validation happens before this
        $this->repository->expects($this->never())
            ->method('calculateTotalIdealFraction');

        // Ensure save and dispatch are not called
        $this->repository->expects($this->never())->method('save');
        $this->messageBus->dispatchCallCount = 0; // Reset for this test

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A fracao ideal deve ser maior o igual que zero e menor ou igual que um');

        ($this->handler)($command);

        $this->assertSame(0, $this->messageBus->dispatchCallCount);
    }
}
