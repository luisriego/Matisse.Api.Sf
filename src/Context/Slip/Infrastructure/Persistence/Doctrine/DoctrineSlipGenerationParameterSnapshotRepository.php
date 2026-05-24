<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Persistence\Doctrine;

use App\Context\Slip\Domain\SlipGenerationParameterSnapshot;
use App\Context\Slip\Domain\SlipGenerationParameterSnapshotRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class DoctrineSlipGenerationParameterSnapshotRepository extends ServiceEntityRepository implements SlipGenerationParameterSnapshotRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SlipGenerationParameterSnapshot::class);
    }

    public function findByExpenseMonth(int $expenseYear, int $expenseMonth): ?SlipGenerationParameterSnapshot
    {
        return $this->findOneBy([
            'expenseYear' => $expenseYear,
            'expenseMonth' => $expenseMonth,
        ]);
    }

    public function upsertForExpenseMonth(
        int $expenseYear,
        int $expenseMonth,
        int $extraFeePerUnitCents,
        int $reserveFundPerUnitCents,
    ): void {
        $existing = $this->findByExpenseMonth($expenseYear, $expenseMonth);

        if ($existing !== null) {
            $existing->updateFees($extraFeePerUnitCents, $reserveFundPerUnitCents);

            return;
        }

        $this->getEntityManager()->persist(new SlipGenerationParameterSnapshot(
            Uuid::v4()->toRfc4122(),
            $expenseYear,
            $expenseMonth,
            $extraFeePerUnitCents,
            $reserveFundPerUnitCents,
        ));
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
