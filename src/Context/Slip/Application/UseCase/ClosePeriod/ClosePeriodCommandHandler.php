<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\ClosePeriod;

use App\Context\Slip\Domain\Exception\NoSlipsToCloseException;
use App\Context\Slip\Domain\PeriodClosure;
use App\Context\Slip\Domain\PeriodClosureRepository;
use App\Context\Slip\Domain\Service\PeriodClosureGuard;
use App\Context\Slip\Domain\SlipRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\ValueObject\Uuid;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use function sprintf;

#[AsMessageHandler]
class ClosePeriodCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly PeriodClosureRepository $periodClosureRepository,
        private readonly SlipRepository $slipRepository,
        private readonly PeriodClosureGuard $periodClosureGuard,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(ClosePeriodCommand $command): void
    {
        $year = $command->year();
        $month = $command->month();

        $this->periodClosureGuard->assertNotClosed($year, $month);

        $dueDateContext = (new DateTimeImmutable(sprintf('%d-%d-01', $year, $month)))->modify('+1 month');
        $dueYear = (int) $dueDateContext->format('Y');
        $dueMonth = (int) $dueDateContext->format('m');

        $slips = $this->slipRepository->findByMonthYear($dueYear, $dueMonth);

        if ($slips === []) {
            throw NoSlipsToCloseException::forMonth($year, $month);
        }

        $closure = PeriodClosure::close(
            Uuid::random()->value(),
            $year,
            $month,
        );

        $this->periodClosureRepository->save($closure);
    }
}
