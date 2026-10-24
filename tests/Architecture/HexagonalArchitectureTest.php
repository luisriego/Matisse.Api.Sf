<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use App\Tests\Shared\Infrastructure\PhpUnit\UnitTestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Finder\Finder;

use function array_filter;
use function array_map;
use function count;
use function is_dir;
use function realpath;

final class HexagonalArchitectureTest extends UnitTestCase
{
    private string $srcRoot;

    protected function setUp(): void
    {
        $this->srcRoot = realpath(__DIR__ . '/../../src');
    }

    public function testDomainFilesDoNotImportInfrastructureOrApplication(): void
    {
        $domainFiles = $this->collectPhpFiles($this->srcRoot, '*/Domain');

        $violations = [];

        foreach ($domainFiles as $file) {
            $content = file_get_contents($file);
            $relativePath = substr($file, strlen($this->srcRoot) + 1);

            if (preg_match_all(
                '#^use\s+([^;\s]+)\;\s*$#m',
                $content,
                $matches
            )) {
                foreach ($matches[1] as $import) {
                    // Allow Symfony Uuid components (pure value factories, no logic)
                    if (strpos($import, 'Symfony\Component\Uid') === 0) {
                        continue;
                    }

                    // Domain must not import Infrastructure
                    if (strpos($import, 'Infrastructure') !== false) {
                        $violations[] = "{$relativePath}: imports {$import}";
                    }

                    // Domain must not import Application
                    if (strpos($import, 'Application') !== false) {
                        $violations[] = "{$relativePath}: imports {$import}";
                    }

                    // Domain must not import Doctrine directly
                    if (strpos($import, 'Doctrine\\') === 0) {
                        $violations[] = "{$relativePath}: imports {$import}";
                    }

                    // Domain must not import HttpFoundation
                    if (strpos($import, 'HttpFoundation') !== false) {
                        $violations[] = "{$relativePath}: imports {$import}";
                    }
                }
            }
        }

        self::assertEmpty(
            $violations,
            "Domain layer violations:\n" . implode("\n", $violations)
        );
    }

    public function testApplicationFilesDoNotImportInfrastructure(): void
    {
        $appFiles = $this->collectPhpFiles($this->srcRoot, '*/Application');

        $violations = [];

        foreach ($appFiles as $file) {
            $content = file_get_contents($file);
            $relativePath = substr($file, strlen($this->srcRoot) + 1);

            if (preg_match_all(
                '#^use\s+([^;\s]+)\;\s*$#m',
                $content,
                $matches
            )) {
                foreach ($matches[1] as $import) {
                    if (strpos($import, 'Infrastructure') !== false) {
                        $violations[] = "{$relativePath}: imports {$import}";
                    }
                }
            }
        }

        self::assertEmpty(
            $violations,
            "Application layer violations:\n" . implode("\n", $violations)
        );
    }

    public function testDomainEventsAreReadonly(): void
    {
        $eventFiles = $this->collectPhpFiles($this->srcRoot, '*/Event');

        $violations = [];

        foreach ($eventFiles as $file) {
            $relativePath = substr($file, strlen($this->srcRoot) + 1);
            $reflector = new ReflectionClass($file);
            $className = $reflector->getName();

            $methods = array_filter(
                array_map(
                    static function (ReflectionMethod $m): string {
                        $name = $m->getName();
                        if (str_starts_with($name, 'set') && !$m->isPublic()) {
                            return $name;
                        }

                        return '';
                    },
                    $reflector->getMethods()
                ),
                static fn(string $name): bool => $name !== ''
            );

            if (count($methods) > 0) {
                $violations[] = "{$relativePath}: has mutating methods: " . implode(', ', $methods);
            }
        }

        self::assertEmpty(
            $violations,
            "Domain events must be readonly:\n" . implode("\n", $violations)
        );
    }

    /**
     * @return list<string>
     */
    private function collectPhpFiles(string $root, string $pattern): array
    {
        $contextDirs = glob($root . '/*/');
        $files = [];

        foreach ($contextDirs as $contextDir) {
            $target = $contextDir . $pattern;
            if (!is_dir($target)) {
                continue;
            }

            $finder = new Finder();
            $finder->in($target)->files()->name('*.php');

            foreach ($finder as $file) {
                $files[] = $file->getRealPath();
            }
        }

        return $files;
    }
}
