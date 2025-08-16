<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Persistence\Doctrine;

use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\SlipRepository;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Domain\ValueObject\DateRange;
use DateMalformedStringException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineSlipRepository extends ServiceEntityRepository implements SlipRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Slip::class);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function save(Slip $Slip, bool $flush = true): void
    {
        $this->getEntityManager()->persist($Slip);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByIdOrFail(string $id): Slip
    {
        if (null === $slip = $this->findOneBy(['id' => $id])) {
            throw ResourceNotFoundException::createFromClassAndId(Slip::class, $id);
        }

        return $slip;
    }

    public function deleteByDateRange(DateRange $dateRange): void
    {
        $this->createQueryBuilder('s')
            ->delete()
            ->where('s.dueDate >= :start_date')
            ->andWhere('s.dueDate <= :end_date')
            ->setParameter('start_date', $dateRange->startDate())
            ->setParameter('end_date', $dateRange->endDate())
            ->getQuery()
            ->execute();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function existsForDueDateMonth(int $year, int $month): bool
    {
        $dateRange = DateRange::fromMonth($year, $month);

        $count = $this->createQueryBuilder('s')
            ->select('count(s.id)')
            ->where('s.dueDate >= :start_date')
            ->andWhere('s.dueDate <= :end_date')
            ->setParameter('start_date', $dateRange->startDate())
            ->setParameter('end_date', $dateRange->endDate())
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }
}
