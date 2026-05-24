<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi;

use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use Symfony\Component\Routing\Route;

/**
 * Assigns OpenAPI tags, summaries and default responses from the route path and name
 * when controllers do not declare them explicitly.
 */
final class BoundedContextRouteDescriber implements RouteDescriberInterface
{
    use RouteDescriberTrait;

    public function describe(OA\OpenApi $api, Route $route, \ReflectionMethod $reflectionMethod): void
    {
        $path = $this->normalizePath($route->getPath());
        $tag = $this->resolveTag($path);
        $summary = $this->summaryFromController($reflectionMethod);
        $isPublic = $this->isPublicPath($path);

        foreach ($this->getOperations($api, $route) as $operation) {
            if ($this->isUndefinedOrEmpty($operation->tags)) {
                $operation->tags = [$tag];
            }

            if (Generator::UNDEFINED === $operation->summary || '' === $operation->summary) {
                $operation->summary = $summary;
            }

            if (!$isPublic && Generator::UNDEFINED === $operation->security) {
                $operation->security = [['bearerAuth' => []]];
            }
        }
    }

    private function resolveTag(string $path): string
    {
        if (!preg_match('#^/api/v1/([^/]+)#', $path, $matches)) {
            return 'Internal';
        }

        return match ($matches[1]) {
            'accounts' => 'Accounts',
            'expenses', 'recurring-expenses', 'expense-types' => 'Expenses',
            'incomes', 'income-types' => 'Incomes',
            'slips', 'periods' => 'Slips',
            'resident-unit' => 'Resident Units',
            'gas' => 'Gas',
            'bank', 'imports' => 'Bank / OFX',
            'setup' => 'Setup',
            'ledger' => 'Ledger',
            'users' => 'Users',
            'login_check' => 'Authentication',
            default => 'Internal',
        };
    }

    private function summaryFromController(\ReflectionMethod $reflectionMethod): string
    {
        $className = $reflectionMethod->getDeclaringClass()->getShortName();
        $withoutSuffix = preg_replace('/Controller$/', '', $className) ?? $className;
        $spaced = trim((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $withoutSuffix));

        return $spaced !== '' ? $spaced : 'API operation';
    }

    private function isPublicPath(string $path): bool
    {
        return str_contains($path, '/health-check')
            || $path === '/api/v1/login_check'
            || str_starts_with($path, '/api/v1/users/register')
            || str_starts_with($path, '/api/v1/users/activate/')
            || $path === '/api/v1/users/password-reset-request'
            || preg_match('#^/api/v1/users/[^/]+/password-reset/[^/]+$#', $path) === 1;
    }

    /**
     * @param mixed $value
     */
    private function isUndefinedOrEmpty(mixed $value): bool
    {
        return Generator::UNDEFINED === $value || [] === $value;
    }
}
