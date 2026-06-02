# Integración Continua (CI) con GitHub Actions

Este documento describe el flujo de trabajo de Integración Continua (`.github/workflows/ci.yml`) configurado para este proyecto.

## Objetivo

El propósito de este pipeline es asegurar la calidad y estabilidad del código base de forma automática. Se encarga de ejecutar una serie de validaciones cada vez que se suben cambios al repositorio.

## Disparadores (Triggers)

El workflow se ejecuta automáticamente en los siguientes eventos:

-   **`push`**: Cada vez que se suben cambios a las ramas `main` o `develop`.
-   **`pull_request`**: Cuando se abre o actualiza un Pull Request que apunta a la rama `main`.

## Proceso del Pipeline

El pipeline consta de un único `job` llamado `build` que se ejecuta en un entorno de `ubuntu-latest`. Los pasos que sigue son:

1.  **Configurar Servicios:**
    -   Inicia un contenedor de **PostgreSQL 16 (pgvector)** que actúa como base de datos temporal para los tests.

2.  **Checkout code:**
    -   Descarga el código fuente de la rama o Pull Request que ha disparado el workflow.

3.  **Setup PHP:**
    -   Configura un entorno con **PHP 8.3**, incluyendo las extensiones `pdo` y `pdo_pgsql`.
    -   Instala Composer v2.

4.  **Cache Composer dependencies:**
    -   Restaura dependencias de Composer cuando `composer.lock` no cambió.

5.  **Install dependencies:**
    -   Ejecuta `composer install --dev`.

6.  **Warm Symfony cache:**
    -   `cache:warmup --env=dev` para PHPStan (container XML).

7.  **Check Coding Standards (PHP-CS-Fixer):**
    -   `composer analyze:standards` sobre `src/` y `tests/`.

8.  **Static analysis (PHPStan):**
    -   `composer analyze:phpstan` (nivel 5, `src/Context` + `src/Shared`).

9.  **Create database schema:**
    -   Crea `app_test`, habilita extensión `vector`, sincroniza schema con `doctrine:schema:update --force --env=test` (misma estrategia que `make db-test-setup`).

10. **Generate JWT keys for test environment:**
    -   Genera claves RSA para Lexik JWT en test.

11. **Run tests:**
    -   `composer test` (PHPUnit completo).

## Alineación con desarrollo local

| Paso | Local (`Makefile`) | CI |
|------|-------------------|-----|
| PostgreSQL | `pgvector/pgvector:pg16` | `pgvector/pgvector:pg16` |
| Extensión vector | `make db-init-extensions` | `CREATE EXTENSION vector` |
| Schema test | `make db-test-setup` → `schema:update` | `schema:update --env=test` |
| Tests | `make tests` | `composer test` |
| Estilo | `make analyze` | `composer analyze:standards` |
| Estático | `make phpstan` | `composer analyze:phpstan` |

**Nota:** Las migraciones en `migrations/` son incrementales para entornos con datos existentes (`make db-migrate`). Para BD vacía de test/dev se usa sincronización desde el mapping Doctrine.
