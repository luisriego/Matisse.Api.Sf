<?php

declare(strict_types=1);

namespace App\Context\Expense\Infrastructure\Persistence\Doctrine;

use App\Context\Expense\Domain\RecurringExpense;
use App\Context\Expense\Domain\RecurringExpenseRepository;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use App\Shared\Domain\ValueObject\DateRange;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function sprintf;

class DoctrineRecurringExpenseRepository extends ServiceEntityRepository implements RecurringExpenseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecurringExpense::class);
    }

    public function findAll(): array
    {
        return $this->findBy(['isActive' => true]);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function save(RecurringExpense $recurringExpense, bool $flush = true): void
    {
        $this->getEntityManager()->persist($recurringExpense);

        if ($flush) {
            $this->flush();
        }
    }

    public function remove(RecurringExpense $recurringExpense, bool $flush = true): void
    {
        $this->getEntityManager()->remove($recurringExpense);

        if ($flush) {
            $this->flush();
        }
    }

    public function findOneByIdOrFail(string $id): RecurringExpense
    {
        if (null === $recurringExpense = $this->find($id)) {
            throw new ResourceNotFoundException(
                sprintf('Resource of type [%s] with ID [%s] not found', RecurringExpense::class, $id),
            );
        }

        return $recurringExpense;
    }

    public function findForThisMonth(int $month): array
    {
        $qb = $this->createQueryBuilder('re');
        $qb->where('JSON_CONTAINS(re.monthsOfYear, :month) = 1')
            ->andWhere('re.isActive = :isActive')
            ->setParameter('month', $month)
            ->setParameter('isActive', true);

        return $qb->getQuery()->getResult();
    }

    public function findActiveForDateRange(DateRange $dateRange): array
    {
        $qb = $this->createQueryBuilder('re');

        $qb->where('re.isActive = :isActive')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        're.startDate <= :endDate',
                        're.endDate IS NULL',
                    ),
                    $qb->expr()->andX(
                        're.startDate <= :endDate',
                        're.endDate >= :startDate',
                    ),
                ),
            )
            ->setParameter('isActive', true)
            ->setParameter('startDate', $dateRange->startDate())
            ->setParameter('endDate', $dateRange->endDate());

        return $qb->getQuery()->getResult();
    }

    public function findByHasPredefinedAmount(bool $hasPredefinedAmount): array
    {
        return $this->findBy(['hasPredefinedAmount' => $hasPredefinedAmount, 'isActive' => true]);
    }

    public function findByYear(int $year): array
    {
        $yearStart = new DateTime("{$year}-01-01 00:00:00");
        $yearEnd = new DateTime("{$year}-12-31 23:59:59");

        $qb = $this->createQueryBuilder('re');

        $qb->where('re.isActive = :isActive')
            ->andWhere('re.startDate <= :yearEnd')
            ->andWhere($qb->expr()->orX(
                're.endDate IS NULL',
                're.endDate >= :yearStart',
            ))
            ->setParameter('isActive', true)
            ->setParameter('yearStart', $yearStart)
            ->setParameter('yearEnd', $yearEnd);

        return $qb->getQuery()->getResult();
    }
}
