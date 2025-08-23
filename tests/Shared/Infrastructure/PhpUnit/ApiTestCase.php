<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\PhpUnit;

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient(['environment' => 'test']);
    }

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }
}
