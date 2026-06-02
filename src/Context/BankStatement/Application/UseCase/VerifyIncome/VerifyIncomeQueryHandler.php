<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\UseCase\VerifyIncome;

use App\Context\BankStatement\Application\Dto\UnpaidSlipDto;
use App\Context\BankStatement\Application\Dto\VerifyIncomeResultDto;
use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\SlipRepository;
use App\Shared\Application\QueryHandler;

use function array_filter;
use function array_map;
use function array_sum;
use function array_values;
use function count;

final class VerifyIncomeQueryHandler implements QueryHandler
{
    public function __construct(
        private readonly SlipRepository $slipRepository,
    ) {}

    public function __invoke(VerifyIncomeQuery $query): VerifyIncomeResultDto
    {
        $slips    = $this->slipRepository->findByMonthYear($query->year, $query->month);
        $received = (int) array_sum(array_map(
            static fn ($line) => $line->amountInCents,
            $query->creditLines,
        ));

        $expected = (int) array_sum(array_map(
            static fn (Slip $s) => $s->amount(),
            $slips,
        ));

        $difference = $received - $expected;

        $status = match (true) {
            $difference === 0 => VerifyIncomeResultDto::STATUS_BALANCED,
            $difference < 0   => VerifyIncomeResultDto::STATUS_SHORTFALL,
            default           => VerifyIncomeResultDto::STATUS_SURPLUS,
        };

        $unpaidSlips = array_values(array_filter(
            $slips,
            static fn (Slip $s) => $s->getStatus() !== 'paid' && $s->getStatus() !== 'cancelled',
        ));

        $paidCount = count($slips) - count($unpaidSlips);

        $unpaidDtos = array_map(
            static fn (Slip $s) => new UnpaidSlipDto(
                slipId: $s->id(),
                amountInCents: $s->amount(),
                status: $s->getStatus(),
                residentUnitId: $s->residentUnit()->id(),
                dueDate: $s->dueDate()->format('Y-m-d'),
            ),
            $unpaidSlips,
        );

        return new VerifyIncomeResultDto(
            expectedInCents: $expected,
            receivedInCents: $received,
            differenceInCents: $difference,
            status: $status,
            totalSlips: count($slips),
            paidSlips: $paidCount,
            unpaidSlips: $unpaidDtos,
        );
    }
}
