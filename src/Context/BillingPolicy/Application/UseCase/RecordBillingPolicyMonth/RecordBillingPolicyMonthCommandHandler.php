<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Application\UseCase\RecordBillingPolicyMonth;

use App\Context\BillingPolicy\Domain\BillingPolicyMonthSnapshotRepository;
use App\Context\BillingPolicy\Domain\Event\MonthlyBillingParametersWereRecorded;
use App\Shared\Application\CommandHandler;
use App\Shared\Application\EventStore;
use App\Shared\Domain\Exception\InvalidDataException;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use function array_map;
use function explode;
use function preg_match;

#[AsMessageHandler]
final readonly class RecordBillingPolicyMonthCommandHandler implements CommandHandler
{
    public function __construct(
        private EventStore $eventStore,
        private BillingPolicyMonthSnapshotRepository $snapshotRepository,
    ) {}

    public function __invoke(RecordBillingPolicyMonthCommand $command): void
    {
        if (1 !== preg_match('/^\d{4}-\d{2}$/', $command->targetMonth())) {
            throw new InvalidDataException('targetMonth must be YYYY-MM.');
        }

        [$year, $month] = array_map('intval', explode('-', $command->targetMonth(), 2));

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            throw new InvalidDataException('targetMonth is out of range.');
        }

        foreach (
            [
                $command->extraFeePerUnitCents(),
                $command->reserveFundPerUnitCents(),
                $command->syndicShareTotalCents(),
            ] as $amount
        ) {
            if ($amount < 0) {
                throw new InvalidDataException('Amounts must be >= 0.');
            }
        }

        $gasPrice = $command->gasPricePerM3Cents();

        if ($gasPrice !== null && $gasPrice < 0) {
            throw new InvalidDataException('gasPricePerM3Cents must be >= 0 when provided.');
        }

        $recordedAt = new DateTimeImmutable();

        $this->eventStore->append(MonthlyBillingParametersWereRecorded::create(
            $command->targetMonth(),
            $command->extraFeePerUnitCents(),
            $command->reserveFundPerUnitCents(),
            $command->syndicShareTotalCents(),
            $gasPrice,
            $command->recordedByUserId(),
        ));

        $this->snapshotRepository->upsert(
            $command->targetMonth(),
            $command->extraFeePerUnitCents(),
            $command->reserveFundPerUnitCents(),
            $command->syndicShareTotalCents(),
            $gasPrice,
            $recordedAt,
        );
        $this->snapshotRepository->flush();
    }
}
