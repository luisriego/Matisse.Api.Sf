<?php

declare(strict_types=1);

namespace App\Context\Slip\Domain\ValueObject;

enum SlipOrigin: string
{
    case GENERATED = 'generated';
    case IMPORTED = 'imported';
}
