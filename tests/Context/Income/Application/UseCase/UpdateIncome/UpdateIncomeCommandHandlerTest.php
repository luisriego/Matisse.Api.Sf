<?php

declare(strict_types=1);

namespace App\Tests\Context\Income\Application\UseCase\UpdateIncome;

use App\Context\Income\Application\UseCase\UpdateIncome\UpdateIncomeCommand;
use App\Context\Income\Application\UseCase\UpdateIncome\UpdateIncomeCommandHandler;
use App\Context\Income\Domain\Income;
use App\Context\Income\Domain\IncomeRepository;
use App\Shared\Domain\Exception\DueDateMustBeInTheFutureException;
use App\Tests\Context\Income\Domain\IncomeIdMother;
use DateMalformedStringException;
use DateTime;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class UpdateIncomeCommandHandlerTest extends TestCase
{
    private IncomeRepository&MockObject $incomeRepository;
    private UpdateIncomeCommandHandler $handler;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->incomeRepository = $this->createMock(IncomeRepository::class);
        $this->handler = new UpdateIncomeCommandHandler($this->incomeRepository);
    }

    /** @test
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function testItUpdatesIncomeWithAllFields(): void
    {
        $incomeId = IncomeIdMother::create();
        $dueDate = (new DateTime('+30 days'))->format('Y-m-d');
        $description = 'Updated description';

        $command = new UpdateIncomeCommand(
            $incomeId->value(),
            $dueDate,
            $description,
        );

        $incomeMock = $this->createMock(Income::class);

        $this->incomeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($incomeId->value())
            ->willReturn($incomeMock);

        $incomeMock
            ->expects(self::once())
            ->method('changeDueDate')
            ->with(self::callback(fn ($dt) => $dt instanceof DateTime && $dt->format('Y-m-d') === $dueDate));

        $incomeMock
            ->expects(self::once())
            ->method('changeDescription')
            ->with($description);

        $this->incomeRepository
            ->expects(self::once())
            ->method('save')
            ->with($incomeMock, true);

        ($this->handler)($command);
    }

    /** @test
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function testItUpdatesOnlyDueDateWhenDescriptionIsNull(): void
    {
        $incomeId = IncomeIdMother::create();
        $dueDate = (new DateTime('+15 days'))->format('Y-m-d');

        $command = new UpdateIncomeCommand(
            $incomeId->value(),
            $dueDate,
            null,
        );

        $incomeMock = $this->createMock(Income::class);

        $this->incomeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($incomeId->value())
            ->willReturn($incomeMock);

        $incomeMock
            ->expects(self::once())
            ->method('changeDueDate')
            ->with(self::callback(fn ($dt) => $dt instanceof DateTime && $dt->format('Y-m-d') === $dueDate));

        $incomeMock
            ->expects(self::never())
            ->method('changeDescription');

        $this->incomeRepository
            ->expects(self::once())
            ->method('save')
            ->with($incomeMock, true);

        ($this->handler)($command);
    }

    /** @test
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function testItUpdatesOnlyDescriptionWhenDueDateIsNull(): void
    {
        $incomeId = IncomeIdMother::create();
        $description = 'Updated description only';

        $command = new UpdateIncomeCommand(
            $incomeId->value(),
            null,
            $description,
        );

        $incomeMock = $this->createMock(Income::class);

        $this->incomeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($incomeId->value())
            ->willReturn($incomeMock);

        $incomeMock
            ->expects(self::never())
            ->method('changeDueDate');

        $incomeMock
            ->expects(self::once())
            ->method('changeDescription')
            ->with($description);

        $this->incomeRepository
            ->expects(self::once())
            ->method('save')
            ->with($incomeMock, true);

        ($this->handler)($command);
    }

    /** @test
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function testItUpdatesNothingWhenAllFieldsAreNull(): void
    {
        $incomeId = IncomeIdMother::create();

        $command = new UpdateIncomeCommand(
            $incomeId->value(),
            null,
            null,
        );

        $incomeMock = $this->createMock(Income::class);

        $this->incomeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($incomeId->value())
            ->willReturn($incomeMock);

        $incomeMock
            ->expects(self::never())
            ->method('changeDueDate');

        $incomeMock
            ->expects(self::never())
            ->method('changeDescription');

        $this->incomeRepository
            ->expects(self::once())
            ->method('save')
            ->with($incomeMock, true);

        ($this->handler)($command);
    }

    /**
     * @test
     */
    public function testItPropagatesExceptionWhenIncomeNotFound(): void
    {
        $incomeId = IncomeIdMother::create();

        $command = new UpdateIncomeCommand(
            $incomeId->value(),
            (new DateTime('+30 days'))->format('Y-m-d'),
            'description',
        );

        $this->incomeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($incomeId->value())
            ->willThrowException(new RuntimeException('Income not found'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Income not found');

        ($this->handler)($command);
    }

    /** @test
     * @throws Exception
     */
    public function testItPropagatesDateMalformedException(): void
    {
        $incomeId = IncomeIdMother::create();

        $command = new UpdateIncomeCommand(
            $incomeId->value(),
            'invalid-date',
            'description',
        );

        $incomeMock = $this->createMock(Income::class);

        $this->incomeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($incomeId->value())
            ->willReturn($incomeMock);

        $this->expectException(DateMalformedStringException::class);

        ($this->handler)($command);
    }

    /** @test
     * @throws Exception
     */
    public function testItPropagatesDueDateMustBeInFutureException(): void
    {
        $incomeId = IncomeIdMother::create();
        $pastDate = (new DateTime('-1 day'))->format('Y-m-d');

        $command = new UpdateIncomeCommand(
            $incomeId->value(),
            $pastDate,
            'description',
        );

        $incomeMock = $this->createMock(Income::class);

        $this->incomeRepository
            ->expects(self::once())
            ->method('findOneByIdOrFail')
            ->with($incomeId->value())
            ->willReturn($incomeMock);

        $this->expectException(DueDateMustBeInTheFutureException::class);

        ($this->handler)($command);
    }
}
