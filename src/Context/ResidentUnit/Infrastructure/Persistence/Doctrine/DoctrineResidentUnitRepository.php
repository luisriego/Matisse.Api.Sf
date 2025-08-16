<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Infrastructure\Persistence\Doctrine;

use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitRepository;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineResidentUnitRepository extends ServiceEntityRepository implements ResidentUnitRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResidentUnit::class);
    }

    public function save(ResidentUnit $residentUnit, bool $flush = true): void
    {
        $this->getEntityManager()->persist($residentUnit);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByIdOrFail(string $id): ResidentUnit
    {
        if (null === $residentUnit = $this->findOneBy(['id' => $id])) {
            throw ResourceNotFoundException::createFromClassAndId(ResidentUnit::class, $id);
        }

        return $residentUnit;
    }

    public function calculateTotalIdealFraction(): float
    {
        $queryBuilder = $this->createQueryBuilder('ru')
            ->select('SUM(ru.idealFraction) as totalFraction');

        return (float) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isActive = :isActive')
            ->andWhere('u.idealFraction > :minFraction')
            ->setParameter('isActive', true)
            ->setParameter('minFraction', 0)
            ->orderBy('u.unit', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
