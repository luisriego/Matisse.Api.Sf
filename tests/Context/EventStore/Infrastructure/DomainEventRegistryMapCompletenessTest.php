<?php

declare(strict_types=1);

namespace App\Tests\Context\EventStore\Infrastructure;

use App\Context\EventStore\Infrastructure\DomainEventRegistryMap;
use App\Shared\Domain\Event\DomainEvent;
use PHPUnit\Framework\TestCase;

use function array_filter;
use function array_map;
use function array_values;
use function class_exists;
use function file_get_contents;
use function glob;
use function is_subclass_of;
use function preg_match;
use function sprintf;

final class DomainEventRegistryMapCompletenessTest extends TestCase
{
    public function testAllDomainEventsInCodebaseAreRegisteredInMap(): void
    {
        $basePath = __DIR__ . '/../../../../src/Context';
        $eventFiles = glob($basePath . '/*/Domain/Event/*.php');

        self::assertNotEmpty($eventFiles, 'Expected at least one domain event file in src/Context/*/Domain/Event/');

        /** @var string[] $eventClasses */
        $eventClasses = array_map(
            static function (string $file): string {
                $content = (string) file_get_contents($file);
                preg_match('/namespace\s+([^;]+);/', $content, $nsMatch);
                preg_match('/class\s+(\w+)\s+extends\s+DomainEvent/', $content, $classMatch);

                if (isset($nsMatch[1], $classMatch[1])) {
                    return $nsMatch[1] . '\\' . $classMatch[1];
                }

                return '';
            },
            $eventFiles,
        );

        // Filter out empty strings and keep only valid subclasses of DomainEvent
        /** @var string[] $validClasses */
        $validClasses = array_values(array_filter(
            $eventClasses,
            static function (string $class): bool {
                return '' !== $class
                    && class_exists($class)
                    && is_subclass_of($class, DomainEvent::class);
            },
        ));

        self::assertNotEmpty($validClasses, 'No valid DomainEvent subclasses found');

        $registryMap = new DomainEventRegistryMap();

        foreach ($validClasses as $eventClass) {
            $eventName = $eventClass::eventName();
            $resolvedClass = $registryMap->getClassForEventType($eventName);

            self::assertSame(
                $eventClass,
                $resolvedClass,
                sprintf(
                    'Event "%s" (class %s) is not registered in DomainEventRegistryMap. Expected %s, got %s.',
                    $eventName,
                    $eventClass,
                    $eventClass,
                    $resolvedClass ?? 'null',
                ),
            );
        }
    }

    public function testUnknownEventTypeReturnsNull(): void
    {
        $registryMap = new DomainEventRegistryMap();

        self::assertNull(
            $registryMap->getClassForEventType('unknown.event.type'),
            'Unknown event types should return null',
        );
    }
}
