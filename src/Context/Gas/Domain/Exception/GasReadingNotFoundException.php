<?php

declare(strict_types=1);

namespace App\Context\Gas\Domain\Exception;

use RuntimeException;

final class GasReadingNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Não foi encontrada uma leitura de gás anterior para esta unidade residencial.');
    }
}
