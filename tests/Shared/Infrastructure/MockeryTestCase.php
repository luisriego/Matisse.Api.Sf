<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase as BaseMockeryTestCase;
use Mockery\MockInterface;

abstract class MockeryTestCase extends BaseMockeryTestCase
{
    protected function mock(string $className): MockInterface
    {
        return Mockery::mock($className);
    }

    protected function mockFinal(string $className): MockInterface
    {
        // Create instance for final class mocking
        $instance = new $className(...$this->resolveConstructorDependencies($className));
        return Mockery::mock($instance);
    }

    private function resolveConstructorDependencies(string $className): array
    {
        $reflectionClass = new \ReflectionClass($className);
        $constructor = $reflectionClass->getConstructor();

        if (!$constructor) {
            return [];
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $dependencies[] = $parameter->isDefaultValueAvailable()
                ? $parameter->getDefaultValue()
                : $this->mock($parameter->getType()->getName());
        }

        return $dependencies;
    }

    protected function similarTo($value)
    {
        return Mockery::on(function ($argument) use ($value) {
            // Handle objects with value comparison
            if (is_object($argument) && is_object($value)) {
                // If both objects have id() method
                if (method_exists($argument, 'id') && method_exists($value, 'id')) {
                    $argId = $argument->id();
                    $valId = $value->id();

                    // If id() returns an object with value() method
                    if (is_object($argId) && is_object($valId) &&
                        method_exists($argId, 'value') && method_exists($valId, 'value')) {
                        return $argId->value() === $valId->value();
                    }

                    // Direct id comparison if they're not objects or don't have value()
                    return $argId === $valId;
                }

                // Try string representation comparison
                return (string)$argument === (string)$value;
            }

            // For non-objects, compare as strings
            return (string)$argument === (string)$value;
        });
    }
}