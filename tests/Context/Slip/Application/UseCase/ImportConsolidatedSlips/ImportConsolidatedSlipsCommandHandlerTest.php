<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Application\UseCase\ImportConsolidatedSlips;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Slip\Application\UseCase\ImportConsolidatedSlips\ImportConsolidatedSlipsCommand;
use App\Context\Slip\Application\UseCase\ImportConsolidatedSlips\ImportConsolidatedSlipsCommandHandler;
use App\Context\Slip\Domain\Exception\PeriodAlreadyClosedException;
use App\Context\Slip\Domain\PeriodClosureRepository;
use App\Context\Slip\Domain\Service\PeriodClosureGuard;
use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\SlipRepository;
use App\Context\Slip\Domain\SlipStatus;
use App\Context\Slip\Domain\ValueObject\SlipOrigin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImportConsolidatedSlipsCommandHandlerTest extends TestCase
{
    private MockObject|SlipRepository $slipRepository;
    private MockObject|ResidentUnitRepository $residentUnitRepository;
    private MockObject|PeriodClosureRepository $periodClosureRepository;
    private ImportConsolidatedSlipsCommandHandler $handler;

    protected function setUp(): void
    {
        $this->slipRepository = $this->createMock(SlipRepository::class);
        $this->residentUnitRepository = $this->createMock(ResidentUnitRepository::class);
        $this->periodClosureRepository = $this->createMock(PeriodClosureRepository::class);

        $guard = new PeriodClosureGuard($this->periodClosureRepository);

        $this->handler = new ImportConsolidatedSlipsCommandHandler(
            $this->slipRepository,
            $this->residentUnitRepository,
            $guard,
        );
    }

    public function testImportCreatesSlipsWithPaidStatusAndImportedOrigin(): void
    {
        $this->periodClosureRepository->method('existsForMonth')->willReturn(false);

        $resident = $this->createConfiguredMock(ResidentUnit::class, [
            'id' => 'unit-1',
            'unit' => '101',
        ]);
        $this->residentUnitRepository->method('findOneByIdOrFail')->willReturn($resident);

        $savedSlips = [];
        $this->slipRepository->expects($this->once())->method('deleteByDateRange');
        $this->slipRepository->expects($this->once())->method('save')
            ->willReturnCallback(function (Slip $slip) use (&$savedSlips): void {
                $savedSlips[] = $slip;
            });
        $this->slipRepository->expects($this->once())->method('flush');

        $command = new ImportConsolidatedSlipsCommand(2025, 1, [
            ['residentUnitId' => 'unit-1', 'amountCents' => 119733],
        ]);

        ($this->handler)($command);

        $this->assertCount(1, $savedSlips);
        $slip = $savedSlips[0];
        $this->assertEquals(119733, $slip->amount());
        $this->assertEquals(SlipStatus::PAID->value, $slip->getStatus());
        $this->assertEquals(SlipOrigin::IMPORTED, $slip->origin());
    }

    public function testImportRejectsClosedPeriod(): void
    {
        $this->periodClosureRepository->method('existsForMonth')->willReturn(true);

        $command = new ImportConsolidatedSlipsCommand(2025, 1, [
            ['residentUnitId' => 'unit-1', 'amountCents' => 100000],
        ]);

        $this->expectException(PeriodAlreadyClosedException::class);
        ($this->handler)($command);
    }

    public function testImportMultipleSlips(): void
    {
        $this->periodClosureRepository->method('existsForMonth')->willReturn(false);

        $resident1 = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'unit-1', 'unit' => '101']);
        $resident2 = $this->createConfiguredMock(ResidentUnit::class, ['id' => 'unit-2', 'unit' => '201']);

        $this->residentUnitRepository->method('findOneByIdOrFail')
            ->willReturnMap([
                ['unit-1', $resident1],
                ['unit-2', $resident2],
            ]);

        $this->slipRepository->expects($this->exactly(2))->method('save');
        $this->slipRepository->expects($this->once())->method('flush');

        $command = new ImportConsolidatedSlipsCommand(2025, 1, [
            ['residentUnitId' => 'unit-1', 'amountCents' => 119733],
            ['residentUnitId' => 'unit-2', 'amountCents' => 119733],
        ]);

        ($this->handler)($command);
    }
}
