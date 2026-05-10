<?php

declare(strict_types=1);

namespace App\Context\Setup\Domain;

enum SyndicAllocationRule: string
{
    case EqualParts = 'equal_parts';
    case IdealFraction = 'ideal_fraction';
}
