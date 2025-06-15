<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use Symfony\Component\HttpFoundation\Request;

use function is_array;
use function json_decode;
use function str_contains;

class RequestTransformer
{
    public function transform(Request $request): void
    {
        // Si el contenido es JSON, decodificarlo y configurarlo en request attributes
        if (str_contains($request->headers->get('Content-Type', ''), 'application/json')) {
            $content = $request->getContent();

            if (!empty($content)) {
                $data = json_decode($content, true);

                if (is_array($data)) {
                    $request->request->replace($data);
                }
            }
        }
    }
}
