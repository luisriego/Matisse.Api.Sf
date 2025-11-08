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
    -   Inicia un contenedor de **PostgreSQL 15** que actúa como base de datos temporal para los tests. Se configura con credenciales de prueba que solo existen durante la ejecución del pipeline.

2.  **Checkout code:**
    -   Descarga el código fuente de la rama o Pull Request que ha disparado el workflow.

3.  **Setup PHP:**
    -   Configura un entorno con **PHP 8.3**, incluyendo las extensiones `pdo` y `pdo_pgsql` necesarias para la base de datos.
    -   Instala la última versión de Composer v2.

4.  **Cache Composer dependencies:**
    -   Utiliza la caché de GitHub Actions para restaurar las dependencias de Composer si el archivo `composer.lock` no ha cambiado. Esto acelera significativamente las ejecuciones posteriores.

5.  **Install dependencies:**
    -   Ejecuta `composer install --dev` para instalar todas las dependencias del proyecto, incluidas las de desarrollo (como PHPUnit).

6.  **Check Coding Standards (PHP-CS-Fixer):**
    -   Ejecuta `composer analyze:standards` para verificar que el código cumple con los estándares de formato definidos. Si el código no está formateado correctamente, el pipeline falla aquí.

7.  **Create database schema:**
    -   Crea la base de datos y el esquema para el entorno de `test` utilizando los comandos de Doctrine.

8.  **Generate JWT keys for test environment:**
    -   Genera las claves `private.pem` y `public.pem` necesarias para que el `LexikJWTAuthenticationBundle` pueda crear tokens en los tests de API.

9.  **Run tests:**
    -   Finalmente, ejecuta la suite completa de tests con el comando `php bin/phpunit`. Si algún test falla, el pipeline se marcará como fallido.