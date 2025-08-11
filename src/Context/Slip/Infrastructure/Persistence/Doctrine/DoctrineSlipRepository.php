<?php

declare(strict_types=1);

namespace App\Context\Slip\Infrastructure\Persistence\Doctrine;

use App\Context\Slip\Domain\Slip;
use App\Context\Slip\Domain\SlipRepository;
use App\Shared\Domain\Exception\ResourceNotFoundException;
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
}