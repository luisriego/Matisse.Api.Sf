<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class RequestArgumentResolver implements ValueResolverInterface
{
    public function __construct(
        private RequestTransformer $requestTransformer,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $argumentType = $argument->getType();

        if (null === $argumentType) {
            return [];
        }

        try {
            $reflectionClass = new ReflectionClass($argumentType);
            if (!$reflectionClass->implementsInterface(RequestDto::class)) {
                return [];
            }

            $this->requestTransformer->transform($request);
            yield new $argumentType($request);
        } catch (\ReflectionException $e) {
            return [];
        }
    }
}