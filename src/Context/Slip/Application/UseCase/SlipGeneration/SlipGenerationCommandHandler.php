<?php

declare(strict_types=1);

namespace App\Context\Slip\Application\UseCase\SlipGeneration;

use App\Context\BillingPolicy\Domain\BillingPolicyResolverPort;
use App\Context\Expense\Domain\ExpenseRepository;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Context\Slip\Domain\Service\PeriodClosureGuard;
use App\Context\Slip\Domain\Service\SlipFactory;
use App\Context\Slip\Domain\Service\SlipGenerationPolicy;
use App\Context\Slip\Domain\SlipGenerationParameterSnapshotRepository;
use App\Context\Slip\Domain\SlipRepository;
use App\Shared\Application\CommandHandler;
use App\Shared\Domain\ValueObject\DateRange;
use DateMalformedStringException;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use function sprintf;

#[AsMessageHandler]
class SlipGenerationCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly SlipRepository $slipRepository,
        private readonly ExpenseRepository $expenseRepository,
        private readonly RecurringExpenseRepository $recurringExpenseRepository,
        private readonly ResidentUnitRepository $residentUnitRepository,
        private readonly SlipGenerationPolicy $generationPolicy,
        private readonly SlipFactory $slipFactory,
        private readonly SlipGenerationParameterSnapshotRepository $slipGenerationParameterSnapshotRepository,
        private readonly PeriodClosureGuard $periodClosureGuard,
        private readonly BillingPolicyResolverPort $billingPolicyResolverService,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function __invoke(SlipGenerationCommand $command): void
    {
        $expenseYear = $command->year();
        $expenseMonth = $command->month();
        $isForced = $command->isForced();

        // 1. Check if generation is allowed according to business rules.
        // Auto-force backfills for past months to avoid policy rejections on historical generation
        $now = new DateTimeImmutable('now');
        $nowYear = (int) $now->format('Y');
        $nowMonth = (int) $now->format('m');
        $isPastMonth = ($expenseYear < $nowYear) || ($expenseYear === $nowYear && $expenseMonth < $nowMonth);

        if ($isPastMonth) {
            $isForced = true;
        }

        $this->periodClosureGuard->assertNotClosed($expenseYear, $expenseMonth);
        $this->generationPolicy->check($expenseYear, $expenseMonth, $isForced);

        // 2. Determine date ranges. The due date is for the month after the expenses.
        $expenseRange = DateRange::fromMonth($expenseYear, $expenseMonth);
        $dueDateContext = (new DateTimeImmutable(sprintf('%d-%d-01', $expenseYear, $expenseMonth)))->modify('+1 month');
        $dueYear = (int) $dueDateContext->format('Y');
        $dueMonth = (int) $dueDateContext->format('m');
        $dueDateRange = DateRange::fromMonth($dueYear, $dueMonth);

        // 3. Delete any existing slips for the due date month before generating new ones.
        $this->slipRepository->deleteByDateRange($dueDateRange);

        // 4. Get all expenses for the period.
        $expenses = $this->expenseRepository->findActiveByDateRange($expenseRange);
        $recurringExpenses = $this->recurringExpenseRepository->findActiveForDateRange($expenseRange);

        // 5. Get all active residential units
        $residentUnits = $this->residentUnitRepository->findAllActive();

        // 6. Use the factory to create the slip aggregates.
        $targetMonth = sprintf('%04d-%02d', $expenseYear, $expenseMonth);
        $policy = $this->billingPolicyResolverService->resolve($targetMonth);
        $extraFeePerUnitCents = $policy->hasPolicy()
            ? $policy->extraFeePerUnitCents()
            : $command->extraFeePerUnitCents();
        $reserveFundPerUnitCents = $policy->hasPolicy()
            ? $policy->reserveFundPerUnitCents()
            : $command->reserveFundPerUnitCents();
        $syndicShareTotalCents = $policy->hasPolicy()
            ? $policy->syndicShareTotalCents()
            : null;

        $slips = $this->slipFactory->createFromExpensesAndUnits(
            $expenses,
            $recurringExpenses,
            $residentUnits,
            $expenseYear,
            $expenseMonth,
            $extraFeePerUnitCents,
            $reserveFundPerUnitCents,
            $syndicShareTotalCents,
        );

        // 7. Persist the new slips.
        foreach ($slips as $slip) {
            $this->slipRepository->save($slip, false);
        }

        $this->slipGenerationParameterSnapshotRepository->upsertForExpenseMonth(
            $expenseYear,
            $expenseMonth,
            $extraFeePerUnitCents,
            $reserveFundPerUnitCents,
        );
        $this->slipRepository->flush();
    }
}
