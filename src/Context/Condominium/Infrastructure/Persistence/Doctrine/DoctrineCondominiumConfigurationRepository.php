<?php

declare(strict_types=1);

namespace App\Context\Condominium\Infrastructure\Persistence\Doctrine;

use App\Context\Condominium\Domain\CondominiumConfiguration;
use App\Context\Condominium\Domain\CondominiumConfigurationRepository;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctrineCondominiumConfigurationRepository extends ServiceEntityRepository implements CondominiumConfigurationRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CondominiumConfiguration::class);
    }

    public function save(CondominiumConfiguration $configuration, bool $flush = true): void
    {
        $this->getEntityManager()->persist($configuration);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOne(): ?CondominiumConfiguration
    {
        return $this->getEntityManager()->getRepository(CondominiumConfiguration::class)->findOneBy([]);
    }

    public function findOrFail(): CondominiumConfiguration
    {
        $configuration = $this->findOne();

        if (null === $configuration) {
            throw ResourceNotFoundException::createFromClassAndId(CondominiumConfiguration::class, 'unique');
        }

        return $configuration;
    }
}
