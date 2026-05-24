<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Persistence\Doctrine;

use App\Context\Slip\Domain\PeriodClosure;
use App\Context\Slip\Domain\PeriodClosureRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrinePeriodClosureRepository extends ServiceEntityRepository implements PeriodClosureRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PeriodClosure::class);
    }

    public function save(PeriodClosure $closure, bool $flush = true): void
    {
        $this->getEntityManager()->persist($closure);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function existsForMonth(int $year, int $month): bool
    {
        $count = $this->createQueryBuilder('pc')
            ->select('count(pc.id)')
            ->where('pc.year = :year')
            ->andWhere('pc.month = :month')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    public function findByMonth(int $year, int $month): ?PeriodClosure
    {
        return $this->findOneBy([
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function deleteByMonth(int $year, int $month): void
    {
        $this->createQueryBuilder('pc')
            ->delete()
            ->where('pc.year = :year')
            ->andWhere('pc.month = :month')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->getQuery()
            ->execute();
    }
}
