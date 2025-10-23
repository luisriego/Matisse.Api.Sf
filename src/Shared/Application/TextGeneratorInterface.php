<?php

declare(strict_types=1);

namespace App\Shared\Application;

interface TextGeneratorInterface
{
    /**
     * @param string $prompt
     * @return string|null
     */
    public function generate(string $prompt): ?string;
}
