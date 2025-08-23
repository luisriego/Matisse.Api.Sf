<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\Exception;

use App\Shared\Domain\Exception\ResourceNotFoundException;

use function sprintf;

final class SlipNotFoundException extends ResourceNotFoundException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Slip with id <%s> not found.', $id));
    }
}
