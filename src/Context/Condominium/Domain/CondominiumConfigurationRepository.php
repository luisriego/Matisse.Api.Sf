<?php

declare(strict_types=1);

namespace App\Context\Condominium\Domain;

use DateTimeImmutable;

interface CondominiumConfigurationRepository
{
    public function save(CondominiumConfiguration $configuration, bool $flush = true): void;

    public function findOne(): ?CondominiumConfiguration;

    public function findOrFail(): CondominiumConfiguration;

    public function findActiveConfigurationForDate(DateTimeImmutable $date): ?CondominiumConfiguration;
}
