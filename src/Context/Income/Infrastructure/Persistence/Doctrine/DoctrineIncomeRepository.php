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
        $qb = $this->createQueryBuilder('i');
        $qb->where('i.isActive = true')
           ->andWhere('i.dueDate >= :startDate')
           ->andWhere('i.dueDate <= :endDate')
           ->orderBy('i.dueDate', 'ASC')
           ->setParameter('startDate', $dateRange->startDate()->format('Y-m-d'))
           ->setParameter('endDate', $dateRange->endDate()->format('Y-m-d'));

        return $qb->getQuery()->getResult();
    }

    public function findInactiveByDateRange(DateRange $dateRange): array
    {
        $qb = $this->createQueryBuilder('i');
        $qb->where('i.isActive = false')
           ->andWhere('i.dueDate >= :startDate')
           ->andWhere('i.dueDate <= :endDate')
           ->orderBy('i.dueDate', 'ASC')
           ->setParameter('startDate', $dateRange->startDate()->format('Y-m-d'))
           ->setParameter('endDate', $dateRange->endDate()->format('Y-m-d'));

        return $qb->getQuery()->getResult();
    }
}
