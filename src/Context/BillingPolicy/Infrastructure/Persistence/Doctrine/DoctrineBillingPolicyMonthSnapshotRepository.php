<?php

declare(strict_types=1);

namespace App\Context\BillingPolicy\Infrastructure\Persistence\Doctrine;

use App\Context\BillingPolicy\Domain\BillingPolicyMonthSnapshot;
use App\Context\BillingPolicy\Domain\BillingPolicyMonthSnapshotRepository;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineBillingPolicyMonthSnapshotRepository extends ServiceEntityRepository implements BillingPolicyMonthSnapshotRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BillingPolicyMonthSnapshot::class);
    }

    public function findAllIndexedByTargetMonth(): array
    {
        /** @var list<BillingPolicyMonthSnapshot> $rows */
        $rows = $this->findAll();
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row->targetMonth()] = $row;
        }

        return $indexed;
    }

    public function upsert(
        string $targetMonth,
        int $extraFeePerUnitCents,
        int $reserveFundPerUnitCents,
        int $syndicShareTotalCents,
        ?int $gasPricePerM3Cents,
        DateTimeImmutable $recordedAt,
    ): void {
        $existing = $this->find($targetMonth);

        if ($existing instanceof BillingPolicyMonthSnapshot) {
            $existing->update(
                $extraFeePerUnitCents,
                $reserveFundPerUnitCents,
                $syndicShareTotalCents,
                $gasPricePerM3Cents,
                $recordedAt,
            );

            return;
        }

        $this->getEntityManager()->persist(new BillingPolicyMonthSnapshot(
            $targetMonth,
            $extraFeePerUnitCents,
            $reserveFundPerUnitCents,
            $syndicShareTotalCents,
            $gasPricePerM3Cents,
            $recordedAt,
        ));
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
