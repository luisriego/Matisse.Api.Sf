<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\PhpUnit;

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\KernelBrowser; // <-- AÑADIDO
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Dotenv\Dotenv;

abstract class ApiTestCase extends WebTestCase
{
    protected ?KernelBrowser $client = null; // <-- PROPIEDAD DECLARADA

    protected function setUp(): void
    {
        // 1. Forzamos el entorno de 'test'
        $_SERVER['APP_ENV'] = 'test';

        // 2. Cargamos las variables de entorno desde el fichero .env en la raíz del proyecto
        if (method_exists(Dotenv::class, 'bootEnv')) {
            // Corregido para subir 4 niveles y encontrar la raíz del proyecto
            (new Dotenv())->bootEnv(dirname(__DIR__, 4) . '/.env');
        }

        // 3. Ahora, llamamos al setUp del padre y creamos el cliente
        parent::setUp();
        $this->client = static::createClient();
    }

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }
}
