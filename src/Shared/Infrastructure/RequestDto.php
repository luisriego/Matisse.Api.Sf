<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use Symfony\Component\HttpFoundation\Request;

interface RequestDto
{
    public function __construct(Request $request);
}