<?php

declare(strict_types=1);

namespace App\Context\Gas\Domain\Exception;

use RuntimeException;

final class GasPriceNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('O preço do gás ainda não foi definido.');
    }
}
