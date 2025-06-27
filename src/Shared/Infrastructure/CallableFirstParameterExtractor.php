<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use ReflectionFunction;
use ReflectionMethod;

final class CallableFirstParameterExtractor
{
    /**
     * @throws \ReflectionException
     */
    public static function forPipedCallables(iterable $callables): array
    {
        $handlers = [];

        foreach ($callables as $callable) {
            if (is_array($callable) && count($callable) === 2) {
                [$object, $method] = $callable;
                $ref = new ReflectionMethod($object, $method);
            } else {
                $ref = new ReflectionFunction($callable);
            }

            $params = $ref->getParameters();
            if (empty($params)) {
                continue;
            }

            $type = $params[0]->getType();
            if ($type === null || $type->isBuiltin()) {
                continue;
            }

            $messageClass = $type->getName();
            $handlers[$messageClass][] = $callable;
        }

        return $handlers;
    }
}