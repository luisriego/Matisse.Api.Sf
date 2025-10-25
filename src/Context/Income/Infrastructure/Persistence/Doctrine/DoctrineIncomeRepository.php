<?php

declare(strict_types=1);

namespace App\Context\Income\Infrastructure\Persistence\Doctrine;

use App\Context\Income\Domain\Income;
use App\Context\Income\Domain\IncomeRepository;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Domain\ValueObject\DateRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineIncomeRepository extends ServiceEntityRepository implements IncomeRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Income::class);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function save(Income $income, bool $flush = true): void
    {
        $this->getEntityManager()->persist($income);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByIdOrFail(string $id): Income
    {
        if (null === $income = $this->findOneBy(['id' => $id])) {
            throw ResourceNotFoundException::createFromClassAndId(Income::class, $id);
        }

        return $income;
    }

    public function findAll(): array
    {
        return $this->findBy([], ['dueDate' => 'ASC']);
    }

    public function findActiveByDateRange(DateRange $dateRange): array
    {
        return $this->getEntityManager()
            ->createQuery('
            SELECT i FROM App\Context\Income\Domain\Income i 
            WHERE i.isActive = true 
            AND i.dueDate >= :startDate 
            AND i.dueDate <= :endDate
            ORDER BY i.dueDate ASC
        ')
            ->setParameter('startDate', $dateRange->startDate())
            ->setParameter('endDate', $dateRange->endDate())
            ->getResult();
    }

    public function findInactiveByDateRange(DateRange $dateRange): array
    {
        return $this->getEntityManager()
            ->createQuery('
            SELECT i FROM App\Context\Income\Domain\Income i 
            WHERE i.isActive = false 
            AND i.dueDate >= :startDate 
            AND i.dueDate <= :endDate
            ORDER BY i.dueDate ASC
        ')
            ->setParameter('startDate', $dateRange->startDate())
            ->setParameter('endDate', $dateRange->endDate())
            ->getResult();
    }
}
