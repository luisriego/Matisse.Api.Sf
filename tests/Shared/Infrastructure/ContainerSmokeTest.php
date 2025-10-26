<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure;

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ContainerSmokeTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // Set DATABASE_URL because phpunit is run with --no-configuration
        // and test environment variables are not being loaded from phpunit.xml.dist
        $_SERVER['DATABASE_URL'] = 'sqlite:///%kernel.project_dir%/var/data_test.db';
    }

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function test_container_compiles_successfully(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        $this->assertNotNull($container, 'The service container should be available after booting the kernel.');
    }
}
