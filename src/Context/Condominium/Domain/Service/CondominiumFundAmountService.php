<?php

declare(strict_types=1);

namespace App\Context\Condominium\Domain\Service;

use App\Context\Condominium\Domain\CondominiumConfiguration;
use App\Context\Condominium\Domain\CondominiumConfigurationRepository;
use App\Shared\Domain\Exception\ResourceNotFoundException;
use DateTimeImmutable;

class CondominiumFundAmountService
{
    public function __construct(
        private CondominiumConfigurationRepository $repository,
    ) {}

    /**
     * @throws ResourceNotFoundException
     */
    public function getActiveConfigurationForDate(DateTimeImmutable $date): CondominiumConfiguration
    {
        // Por ahora, simplemente obtenemos la única configuración existente.
        // En un escenario más complejo, se buscaría la configuración cuya effectiveDate
        // sea la más reciente pero anterior o igual a la fecha proporcionada.
        return $this->repository->findOrFail();
    }
}
