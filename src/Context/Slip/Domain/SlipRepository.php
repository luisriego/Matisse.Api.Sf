<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain;

use App\Shared\Domain\ValueObject\DateRange;

interface SlipRepository
{
    public function flush(): void;

    public function save(Slip $Slip, bool $flush = true): void;

    public function findOneByIdOrFail(string $id): Slip;
}