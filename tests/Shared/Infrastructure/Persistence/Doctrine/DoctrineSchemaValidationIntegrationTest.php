<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaValidator;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversNothing]
final class DoctrineSchemaValidationIntegrationTest extends KernelTestCase
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var SchemaValidator */
    private $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $manager = self::$kernel->getContainer()->get('doctrine')->getManager();
        $this->entityManager = $manager instanceof EntityManagerInterface
            ? $manager
            : throw new \RuntimeException('Expected EntityManagerInterface');
        $this->validator = new SchemaValidator($this->entityManager);
    }

    /** @group integration-schema */
    public function test_expense_context_mapping_is_valid(): void
    {
        /** @var array<string, list<string>> $errors */
        $errors = $this->validator->validateMapping();
        $expenseErrors = [];
        foreach ($errors as $class => $messages) {
            if (str_starts_with($class, 'App\Context\Expense\Domain\\')) {
                $expenseErrors[$class] = $messages;
            }
        }

        Assert::assertEmpty(
            $expenseErrors,
            'Expense mapping errors: ' . implode("\n", array_map(
                fn (string $class) => sprintf('%s: %s', $class, implode(', ', $errors[$class])),
                array_keys($expenseErrors)
            ))
        );
    }

    /** @group integration-schema */
    public function test_income_context_mapping_is_valid(): void
    {
        /** @var array<string, list<string>> $errors */
        $errors = $this->validator->validateMapping();
        $incomeErrors = [];
        foreach ($errors as $class => $messages) {
            if (str_starts_with($class, 'App\Context\Income\Domain\\')) {
                $incomeErrors[$class] = $messages;
            }
        }

        Assert::assertEmpty(
            $incomeErrors,
            'Income mapping errors: ' . implode("\n", array_map(
                fn (string $class) => sprintf('%s: %s', $class, implode(', ', $errors[$class])),
                array_keys($incomeErrors)
            ))
        );
    }

    /** @group integration-schema */
    public function test_slip_context_mapping_is_valid(): void
    {
        /** @var array<string, list<string>> $errors */
        $errors = $this->validator->validateMapping();
        $slipErrors = [];
        foreach ($errors as $class => $messages) {
            if (str_starts_with($class, 'App\Context\Slip\Domain\\')) {
                $slipErrors[$class] = $messages;
            }
        }

        Assert::assertEmpty(
            $slipErrors,
            'Slip mapping errors: ' . implode("\n", array_map(
                fn (string $class) => sprintf('%s: %s', $class, implode(', ', $errors[$class])),
                array_keys($slipErrors)
            ))
        );
    }

    /** @group integration-schema */
    public function test_no_bidirectional_cross_context_associations(): void
    {
        /** @var array<\Doctrine\Persistence\Mapping\ClassMetadata> $allMetadata */
        $allMetadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        foreach ($allMetadata as $classMetadata) {
            $name = $classMetadata->getName();
            $assocMappings = $classMetadata->associationMappings ?? [];

            foreach ($assocMappings as $assoc) {
                // Only check ManyToOne associations that are bidirectional (have mappedBy)
                if (isset($assoc['mappedBy'])) {
                    $targetEntity = $assoc['targetEntity'] ?? '';
                    $sourceContext = $this->extractContext($name);
                    $targetContext = $this->extractContext($targetEntity);

                    // Only flag cross-context bidirectional associations
                    if ($sourceContext !== null && $targetContext !== null && $sourceContext !== $targetContext) {
                        Assert::fail(
                            sprintf(
                                'Cross-context bidirectional association detected: %s.%s -> %s (mappedBy=%s). Convert to unidirectional by removing the inverse side.',
                                $name,
                                $assoc['fieldName'],
                                $targetEntity,
                                $assoc['mappedBy']
                            )
                        );
                    }
                }
            }
        }

        // All checks passed — no cross-context bidirectional associations found
    }

    private function extractContext(string $className): ?string
    {
        $parts = explode('\\', $className);
        if (isset($parts[1]) && $parts[0] === 'App' && $parts[1] === 'Context') {
            return $parts[2] ?? null;
        }
        return null;
    }
}
