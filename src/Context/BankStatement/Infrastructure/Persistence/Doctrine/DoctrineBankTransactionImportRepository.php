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

    public function existsByImportLineKey(string $importLineKey, string $bankAccountId): bool
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.importLineKey = :importLineKey')
            ->andWhere('i.bankAccountId = :bankAccountId')
            ->setParameter('importLineKey', $importLineKey)
            ->setParameter('bankAccountId', $bankAccountId)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function findImportedLineKeys(string $bankAccountId, array $importLineKeys): array
    {
        if (empty($importLineKeys)) {
            return [];
        }

        $rows = $this->createQueryBuilder('i')
            ->select('i.importLineKey')
            ->where('i.bankAccountId = :bankAccountId')
            ->andWhere('i.importLineKey IN (:importLineKeys)')
            ->setParameter('bankAccountId', $bankAccountId)
            ->setParameter('importLineKeys', $importLineKeys)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row) => $row['importLineKey'], $rows);
    }
}
