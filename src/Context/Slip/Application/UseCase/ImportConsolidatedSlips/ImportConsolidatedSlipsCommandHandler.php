<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\ImportConsolidatedSlips;

use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Slip\Domain\Service\PeriodClosureGuard;
use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\SlipRepository;
use App\Context\Slip\Domain\ValueObject\SlipAmount;
use App\Context\Slip\Domain\ValueObject\SlipDueDate;
use App\Context\Slip\Domain\ValueObject\SlipId;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\ValueObject\DateRange;
use App\Shared\Domain\ValueObject\Uuid;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use function sprintf;

#[AsMessageHandler]
class ImportConsolidatedSlipsCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly SlipRepository $slipRepository,
        private readonly ResidentUnitRepository $residentUnitRepository,
        private readonly PeriodClosureGuard $periodClosureGuard,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(ImportConsolidatedSlipsCommand $command): void
    {
        $expenseYear = $command->year();
        $expenseMonth = $command->month();

        $this->periodClosureGuard->assertNotClosed($expenseYear, $expenseMonth);

        $dueDateContext = (new DateTimeImmutable(sprintf('%d-%d-01', $expenseYear, $expenseMonth)))->modify('+1 month');
        $dueYear = (int) $dueDateContext->format('Y');
        $dueMonth = (int) $dueDateContext->format('m');
        $dueDateRange = DateRange::fromMonth($dueYear, $dueMonth);

        $this->slipRepository->deleteByDateRange($dueDateRange);

        $dueDateTime = SlipDueDate::selectDueDate($dueYear, $dueMonth);
        $dueDate = new SlipDueDate($dueDateTime);

        foreach ($command->slips() as $slipData) {
            $residentUnit = $this->residentUnitRepository->findOneByIdOrFail($slipData['residentUnitId']);

            $slip = Slip::importForUnit(
                new SlipId(Uuid::random()->value()),
                new SlipAmount($slipData['amountCents']),
                $residentUnit,
                $dueDate,
            );

            $this->slipRepository->save($slip, false);
        }

        $this->slipRepository->flush();
    }
}
