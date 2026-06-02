<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Application\UseCase\GetSlipDetails;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\Slip\Application\UseCase\GetSlipDetails\GetSlipDetailsQuery;
use App\Context\Slip\Application\UseCase\GetSlipDetails\GetSlipDetailsQueryHandler;
use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\SlipRepository;
use App\Context\Slip\Domain\SlipStatus;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Tests\Shared\Domain\UuidMother;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetSlipDetailsQueryHandlerTest extends TestCase
{
    private GetSlipDetailsQueryHandler $handler;
    private MockObject|SlipRepository $mockRepository;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRepository = $this->createMock(SlipRepository::class);
        $this->handler = new GetSlipDetailsQueryHandler($this->mockRepository);
    }

    /**
     * @throws Exception
     */
    public function testInvokeWithValidSlip(): void
    {
        $slipId = UuidMother::create();
        $query = new GetSlipDetailsQuery($slipId);

        $residentUnit = $this->createMock(ResidentUnit::class);
        $residentUnit->method('id')->willReturn('ru-123');
        $residentUnit->method('unit')->willReturn('AP-101');
        $residentUnit->method('idealFraction')->willReturn(0.25);

        $slip = $this->createMock(Slip::class);
        $slip->method('id')->willReturn($slipId);
        $slip->method('getStatus')->willReturn(SlipStatus::PENDING->value);
        $slip->method('amount')->willReturn(15000);
        $slip->method('dueDate')->willReturn(new DateTimeImmutable('2023-12-31'));
        $slip->method('description')->willReturn('Test Slip Description');
        $slip->method('createdAt')->willReturn(new DateTimeImmutable('2023-12-01 10:00:00'));
        $slip->method('residentUnit')->willReturn($residentUnit);

        $this->mockRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($this->callback(fn ($arg) => $arg->value() === $slipId))
            ->willReturn($slip);

        $expected = [
            'id' => $slipId,
            'status' => SlipStatus::PENDING->value,
            'amount' => 15000,
            'dueDate' => '2023-12-31',
            'description' => 'Test Slip Description',
            'createdAt' => '2023-12-01 10:00:00',
            'residentUnit' => [
                'id' => 'ru-123',
                'unit' => 'AP-101',
                'idealFraction' => 0.25,
            ],
        ];

        $result = ($this->handler)($query);
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws Exception
     */
    public function testInvokeWithSlipHavingNullDescription(): void
    {
        $slipId = UuidMother::create();
        $query = new GetSlipDetailsQuery($slipId);

        $residentUnit = $this->createMock(ResidentUnit::class);
        $residentUnit->method('id')->willReturn('ru-456');
        $residentUnit->method('unit')->willReturn('AP-202');
        $residentUnit->method('idealFraction')->willReturn(0.50);

        $slip = $this->createMock(Slip::class);
        $slip->method('id')->willReturn($slipId);
        $slip->method('getStatus')->willReturn(SlipStatus::PAID->value);
        $slip->method('amount')->willReturn(20000);
        $slip->method('dueDate')->willReturn(new DateTimeImmutable('2024-01-15'));
        $slip->method('description')->willReturn(null); // Null description
        $slip->method('createdAt')->willReturn(new DateTimeImmutable('2024-01-01 12:00:00'));
        $slip->method('residentUnit')->willReturn($residentUnit);

        $this->mockRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->willReturn($slip);

        $result = ($this->handler)($query);
        $this->assertNull($result['description']);
    }

    public function testInvokeWhenSlipNotFound(): void
    {
        $slipId = UuidMother::create();
        $query = new GetSlipDetailsQuery($slipId);

        $this->mockRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->with($this->callback(fn ($arg) => $arg->value() === $slipId)) // Check the value inside the ValueObject
            ->willThrowException(new ResourceNotFoundException());

        $this->expectException(ResourceNotFoundException::class);

        ($this->handler)($query);
    }

    public function testInvokeWithZeroAmountSlip(): void
    {
        $slipId = UuidMother::create();
        $query = new GetSlipDetailsQuery($slipId);

        $slip = $this->createMock(Slip::class);
        $slip->method('id')->willReturn($slipId);
        $slip->method('amount')->willReturn(0); // Zero amount
        $slip->method('residentUnit')->willReturn($this->createMock(ResidentUnit::class));
        $slip->method('getStatus')->willReturn(SlipStatus::PENDING->value);
        $slip->method('dueDate')->willReturn(new DateTimeImmutable());
        $slip->method('createdAt')->willReturn(new DateTimeImmutable());

        $this->mockRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->willReturn($slip);

        $result = ($this->handler)($query);
        $this->assertSame(0, $result['amount']);
    }

    public function testInvokeWithNegativeAmountSlip(): void
    {
        $slipId = UuidMother::create();
        $query = new GetSlipDetailsQuery($slipId);

        $slip = $this->createMock(Slip::class);
        $slip->method('id')->willReturn($slipId);
        $slip->method('amount')->willReturn(-5000); // Negative amount
        $slip->method('residentUnit')->willReturn($this->createMock(ResidentUnit::class));
        $slip->method('getStatus')->willReturn(SlipStatus::PENDING->value);
        $slip->method('dueDate')->willReturn(new DateTimeImmutable());
        $slip->method('createdAt')->willReturn(new DateTimeImmutable());

        $this->mockRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->willReturn($slip);

        $result = ($this->handler)($query);
        $this->assertSame(-5000, $result['amount']);
    }

    public function testInvokeWithEmptyStringDescription(): void
    {
        $slipId = UuidMother::create();
        $query = new GetSlipDetailsQuery($slipId);

        $slip = $this->createMock(Slip::class);
        $slip->method('id')->willReturn($slipId);
        $slip->method('description')->willReturn(''); // Empty string description
        $slip->method('residentUnit')->willReturn($this->createMock(ResidentUnit::class));
        $slip->method('getStatus')->willReturn(SlipStatus::PENDING->value);
        $slip->method('amount')->willReturn(100);
        $slip->method('dueDate')->willReturn(new DateTimeImmutable());
        $slip->method('createdAt')->willReturn(new DateTimeImmutable());

        $this->mockRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->willReturn($slip);

        $result = ($this->handler)($query);
        $this->assertSame('', $result['description']);
    }

    public function testInvokeWithExtremeDueDate(): void
    {
        $slipId = UuidMother::create();
        $query = new GetSlipDetailsQuery($slipId);
        $farFutureDate = new DateTimeImmutable('2099-12-31');

        $slip = $this->createMock(Slip::class);
        $slip->method('id')->willReturn($slipId);
        $slip->method('dueDate')->willReturn($farFutureDate);
        $slip->method('residentUnit')->willReturn($this->createMock(ResidentUnit::class));
        $slip->method('getStatus')->willReturn(SlipStatus::PENDING->value);
        $slip->method('amount')->willReturn(100);
        $slip->method('description')->willReturn('Future Slip');
        $slip->method('createdAt')->willReturn(new DateTimeImmutable());

        $this->mockRepository->expects($this->once())
            ->method('findOneByIdOrFail')
            ->willReturn($slip);

        $result = ($this->handler)($query);
        $this->assertSame('2099-12-31', $result['dueDate']);
    }
}
