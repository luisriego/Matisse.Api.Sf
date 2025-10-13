<?php

declare(strict_types=1);

namespace App\Context\Income\Infrastructure\Persistence\Doctrine;

use App\Context\Income\Domain\IncomeType;
use App\Context\Income\Domain\IncomeTypeRepository;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineIncomeTypeRepository extends ServiceEntityRepository implements IncomeTypeRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IncomeType::class);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function save(IncomeType $incomeType, bool $flush = true): void
    {
        $this->getEntityManager()->persist($incomeType);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByIdOrFail(string $id): IncomeType
    {
        if (null === $income = $this->findOneBy(['id' => $id])) {
            throw ResourceNotFoundException::createFromClassAndId(IncomeType::class, $id);
        }

        return $income;
    }
}
