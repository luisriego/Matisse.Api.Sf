<?php

declare(strict_types=1);

namespace App\Context\ResidentUnit\Domain\Exception;

use App\Shared\Domain\Exception\ResourceNotFoundException;

final class ResidentUnitNotFoundException extends ResourceNotFoundException
{
    public function __construct()
    {
        parent::__construct('Resident unit not found');
    }
}
