<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Persistence\Doctrine;

use App\Context\BankStatement\Domain\BankTransactionImport;
use App\Context\BankStatement\Domain\BankTransactionImportRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function array_column;
use function array_map;

final class DoctrineBankTransactionImportRepository extends ServiceEntityRepository implements BankTransactionImportRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BankTransactionImport::class);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function save(BankTransactionImport $import, bool $flush = true): void
    {
        $this->getEntityManager()->persist($import);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function existsByFitId(string $fitId, string $bankAccountId): bool
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.fitId = :fitId')
            ->andWhere('i.bankAccountId = :bankAccountId')
            ->setParameter('fitId', $fitId)
            ->setParameter('bankAccountId', $bankAccountId)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function findImportedFitIds(string $bankAccountId, array $fitIds): array
    {
        if (empty($fitIds)) {
            return [];
        }

        $rows = $this->createQueryBuilder('i')
            ->select('i.fitId')
            ->where('i.bankAccountId = :bankAccountId')
            ->andWhere('i.fitId IN (:fitIds)')
            ->setParameter('bankAccountId', $bankAccountId)
            ->setParameter('fitIds', $fitIds)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row) => $row['fitId'], $rows);
    }
}
