<?php

declare(strict_types=1);

namespace App\Shared\Application;

interface TextGeneratorInterface
{
    public function generate(string $prompt): ?string;
}
