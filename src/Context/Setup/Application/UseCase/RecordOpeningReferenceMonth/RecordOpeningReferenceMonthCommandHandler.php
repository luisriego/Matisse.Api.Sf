<?php

declare(strict_types=1);

namespace App\Context\Setup\Application\UseCase\RecordOpeningReferenceMonth;

use App\Context\Setup\Application\Service\SetupFinalizationService;
use App\Context\Setup\Domain\Event\OpeningReferenceMonthWasRecorded;
use App\Context\Setup\Domain\SyndicAllocationRule;
use App\Shared\Application\CommandHandler;
use App\Shared\Application\EventStore;
use App\Shared\Domain\Exception\InvalidDataException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use function preg_match;

#[AsMessageHandler]
final readonly class RecordOpeningReferenceMonthCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly EventStore $eventStore,
        private readonly SetupFinalizationService $setupFinalization,
    ) {}

    public function __invoke(RecordOpeningReferenceMonthCommand $command): void
    {
        if (1 !== preg_match('/^\d{4}-\d{2}$/', $command->referenceMonth())) {
            throw new InvalidDataException('referenceMonth must be YYYY-MM.');
        }

        [$y, $m] = array_map('intval', explode('-', $command->referenceMonth(), 2));
        if ($m < 1 || $m > 12) {
            throw new InvalidDataException('referenceMonth month must be 01-12.');
        }
        if ($y < 2000 || $y > 2100) {
            throw new InvalidDataException('referenceMonth year must be plausible.');
        }

        if (SyndicAllocationRule::tryFrom($command->syndicAllocationRule()) === null) {
            throw new InvalidDataException(
                'syndicAllocationRule must be "equal_parts" or "ideal_fraction".',
            );
        }

        if ($command->extraFeePerUnitCents() < 0 || $command->reserveFundPerUnitCents() < 0) {
            throw new InvalidDataException('Per-unit fee amounts must be >= 0.');
        }

        foreach (
            [
                $command->expectedCommonExpensesCents(),
                $command->expectedSyndicShareTotalCents(),
                $command->expectedBoletoTotalCents(),
                $command->optionalGasTotalCents(),
            ] as $optional
        ) {
            if ($optional !== null && $optional < 0) {
                throw new InvalidDataException('Optional demonstrative totals must be >= 0 when provided.');
            }
        }

        $rule = SyndicAllocationRule::from($command->syndicAllocationRule());

        $this->eventStore->append(OpeningReferenceMonthWasRecorded::create(
            $command->referenceMonth(),
            $rule,
            $command->extraFeePerUnitCents(),
            $command->reserveFundPerUnitCents(),
            $command->expectedCommonExpensesCents(),
            $command->expectedSyndicShareTotalCents(),
            $command->expectedBoletoTotalCents(),
            $command->optionalGasTotalCents(),
            $command->ledgerAccountId(),
        ));

        $this->setupFinalization->tryFinalizeWhenCoreComplete();
    }
}
