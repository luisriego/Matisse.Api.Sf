# Estrategia de Testing Estándar de la Industria (`Matisse.Api.Sf`)

Este documento define la taxonomía completa de pruebas estándar en la industria aplicada a `Matisse.Api.Sf`, su ubicación en el repositorio, sus contratos de aislamiento y cómo cada nivel se integra como **Oráculo** dentro del Sistema Atómico Cleanroom (`tools/cleanroom/gate.sh`).

---

## 1. Taxonomía de los 8 Niveles de Prueba

```
                      ┌───────────────────────────────┐
                      │ 8. Mutation Testing (Infection)│  ◄── Audita la eficacia real de los asertos
                      └───────────────┬───────────────┘
                                      │
                  ┌───────────────────┴───────────────────┐
                  │   7. Acceptance / E2E API Scenarios   │  ◄── Flujos multi-endpoint (Given/When/Then)
                  ├───────────────────────────────────────┤
                  │   6. Contract & OpenAPI Schema Tests  │  ◄── /api/v1/doc.json + JSON Schema + Smoke
                  ├───────────────────────────────────────┤
                  │   5. Security & Auth Boundary Tests   │  ◄── JWT, ROLE_SYNDIC vs Resident, Setup Guard
                  ├───────────────────────────────────────┤
                  │   4. HTTP Controller Integration      │  ◄── RequestDto -> Handler -> DB -> EventStore
                  ├───────────────────────────────────────┤
                  │   3. Repository & Outbox Integration  │  ◄── Doctrine ORM XML + PostgreSQL 16/pgvector
                  ├───────────────────────────────────────┤
                  │   2. Architecture & Layering Fitness  │  ◄── Regla de Dependencia Hexagonal + DDD
                  ├───────────────────────────────────────┤
                  │   1. Pure Unit Tests (Domain + App)   │  ◄── 0 ms I/O, Object Mothers, MockeryTestCase
                  └───────────────────────────────────────┘
```

| # | Nivel de Test | Suite PHPUnit / Herramienta | Directorio | Base Class / Dobles | Base de Datos / Red | Tiempo Objetivo |
|---|---|---|---|---|---|---|
| **1** | **Unitario (`unit`)** | `--testsuite=unit` | `tests/Context/*/Domain`<br>`tests/Context/*/Application` | `TestCase`, `UnitTestCase`, `*ModuleUnitTestCase` + `ObjectMother` | **PROHIBIDO** (0 I/O) | $< 5\text{ s}$ total ($< 15\text{ ms}$/test) |
| **2** | **Arquitectura (`architecture`)** | `--testsuite=architecture` + `PHPStan` | `tests/Architecture/` | `TestCase` (Reflexión / AST de namespaces) | **PROHIBIDO** | $< 1\text{ s}$ |
| **3** | **Integración de Persistencia (`integration`)** | `--testsuite=authenticated_integration` | `tests/Context/*/Infrastructure/Persistence` | `KernelTestCase` / `ApiTestCase` + `DAMA\DoctrineTestBundle` | PostgreSQL 16 (`app_test`) con rollback transaccional | $< 100\text{ ms}$/test |
| **4** | **Integración HTTP (`authenticated_integration`)** | `--testsuite=authenticated_integration` | `tests/Context/*/Infrastructure/Http/Controller` | `ApiTestCase` (`createAuthenticatedClient`) | PostgreSQL 16 (`app_test`) + `EventStore` | $< 150\text{ ms}$/test |
| **5** | **Seguridad y Autorización (`security`)** | `--testsuite=authenticated_integration` | `tests/Context/User/Infrastructure/Security`<br>`tests/Security/` | `ApiTestCase` (`asSyndic: false`, anónimo, inactivo, setup pendiente) | PostgreSQL 16 (`app_test`) | $< 150\text{ ms}$/test |
| **6** | **Aceptación / E2E API (`acceptance`)** | `--testsuite=acceptance` | `tests/Acceptance/` (o `tests/Context/*/Acceptance`) | `ApiTestCase` (Escenarios Given/When/Then multi-paso) | PostgreSQL 16 (`app_test`) | $< 300\text{ ms}$/escenario |
| **7** | **Contrato y Smoke (`shared` / `contract`)** | `--testsuite=shared` | `tests/Shared/Infrastructure` | `KernelTestCase` / `ApiTestCase` | Contenedor compilado + OpenAPI | $< 2\text{ s}$ |
| **8** | **Mutación (`mutation`)** | `vendor/bin/infection` | `src/Context/*/Domain` | Ejecuta unit tests contra mutantes AST | Sin red | Bajo demanda en átomos de dominio (`MSI >= 80%`) |

---

## 2. Reglas Estrictas por Nivel

### Nivel 1 — Unit Testing (`unit`)
- **Alcance**:
  - **Dominio (`Domain/`)**: Agregados (`User`, `ResidentUnit`, `Account`, `Expense`, `RecurringExpense`, `Income`, `Gas`, `Slip`, `PeriodClosure`, `BankTransactionImport`), Value Objects, Políticas (`IdealFractionSumPolicy`, `SlipGenerationPolicy`), Guards (`PeriodClosureGuard`) y Servicios de Dominio (`SlipComponentBreakdownService`, `SyndicFeeSlipPoolAdjustmentService`, `MonthlyExpenseAggregatorService`, `RecurringExpenseSlipContributionService`, `SlipFactory`, `BillingPolicyResolver`).
  - **Aplicación (`Application/`)**: `CommandHandler`, `QueryHandler` y Servicios de Aplicación (`CatalogSeeder`, `SetupFinalizationService`, `SlipWorkflowService`) con repositorios y puertos **100% doblados** (`Mockery` o `createMock`).
- **Invariantes del Nivel**:
  1. Ningún test bajo `tests/Context/*/Domain` o `tests/Context/*/Application` puede extender `ApiTestCase` ni abrir conexión a PostgreSQL.
  2. Construcción de datos mediante **Object Mothers** (`*Mother.php`).
  3. Verificación obligatoria de:
     - Estado resultante del agregado o retorno del servicio/handler.
     - Eventos de dominio grabados (`$aggregate->pullDomainEvents()`).
     - Fronteras de precisión numérica (ej. tolerancia `1e-5` en fracciones ideales, resto de división en céntimos).
     - Excepciones de dominio exactas ante violación de invariantes.

### Nivel 2 — Architecture & Fitness Functions (`architecture`)
- **Alcance**: Garantizar automáticamente que ningún cambio futuro rompa la Arquitectura Hexagonal ni el aislamiento de Bounded Contexts:
  1. **Pureza del Dominio**: Ninguna clase en `App\Context\*\Domain\**` ni `App\Shared\Domain\**` puede importar `Symfony\Component\HttpFoundation\*`, `Doctrine\ORM\*`, `Doctrine\DBAL\*`, ni clases de `Application` o `Infrastructure` (con la única excepción conocida e inventariada de `UserInterface`/`PasswordAuthenticatedUserInterface` en `App\Context\User\Domain\User`).
  2. **Aislamiento de Aplicación**: Ninguna clase en `App\Context\*\Application\**` puede depender de `App\Context\*\Infrastructure\**` (debe inyectar interfaces del Dominio).
  3. **Convención de Controladores y Eventos**: Todos los eventos de dominio extienden `App\Shared\Domain\Event\DomainEvent` y están registrados en `DomainEventRegistryMap` si son reconstruibles desde `StoredEvent`.

### Nivel 3 y 4 — Integration Testing (`authenticated_integration`)
- **Alcance**:
  - **Persistencia**: Verificar que los mapeos XML (`*.orm.xml`), tipos personalizados (`VectorType` para `pgvector`) y consultas DQL/SQL en `Doctrine*Repository` persisten y recuperan fielmente los agregados.
  - **HTTP + Outbox**: Enviar peticiones reales mediante `KernelBrowser` (`$this->client->request(...)`), pasando por `RequestArgumentResolver` -> `RequestDto` -> `Controller` -> `CommandHandler`/`QueryHandler` -> `Doctrine` -> `DomainEventCollectorSubscriber` -> `StoredEvent` (`event_store`).
- **Aserciones mínimas en comandos mutables**:
  1. Código HTTP exacto (`201 Created`, `200 OK`, `204 No Content`, `400 Bad Request`, `404 Not Found`, `409 Conflict`, `422 Unprocessable Entity`).
  2. Estado persistido en base de datos (`$repository->findOneByIdOrFail(...)`).
  3. Evento de dominio persistido en `event_store` cuando el agregado emite eventos.

### Nivel 5 — Security & Authorization Boundary Testing (`security`)
- **Alcance**:
  1. **Sin JWT (`401 Unauthorized`)**: Endpoints bajo `/api/v1/*` (salvo rutas públicas `/api/v1/login_check`, `/api/v1/users/register`, `/api/v1/users/activate`, `/api/v1/*/health-check`, `/api/v1/doc`).
  2. **Usuario Inactivo (`401 Unauthorized`)**: `UserIsActiveChecker` impide emitir token o autenticar cuentas con `isActive === false`.
  3. **Matriz RBAC (`ROLE_SYNDIC` vs `ROLE_USER` residente)**:
     - Escrituras financieras, de configuración, de unidades, de política mensual y de cierre/generación de cuotas exigen `ROLE_SYNDIC` (`403 Forbidden` para un residente autenticado con `asSyndic: false`, verificado en `SyndicWriteAuthorizationTest`).
  4. **Guardia de Setup (`SetupRequiredListener`)**: Mientras el condominio no haya emitido `setup.was.completed`, las rutas operativas no exentas (como `/api/v1/slips/*`, `/api/v1/bank-statements/*`, `/api/v1/forecast/*`, `/api/v1/ledger/*`) devuelven `403 Forbidden` con `"error": "SETUP_REQUIRED"`.

### Nivel 6 — Acceptance / End-to-End Business Scenarios (`acceptance`)
- **Alcance**: Un átomo de aceptación valida una **promesa de negocio completa** atravesando múltiples endpoints HTTP en secuencia, tal como la consume el frontend o el operador, sin tocar repositorios por detrás salvo cuando se requiere fijar el reloj (`Clock`).
- **Escenarios canónicos en `hecho.md`**:
  - **`TEN-03` (`SPEC-TEN`)**: Completar los pasos de apertura y llamar a `POST /api/v1/setup/finalize` cierra el setup (`200`/`204`), habilita las rutas operativas y una segunda llamada o consulta a `GET /api/v1/setup/status` confirma `isFinalized: true` sin volver a exigir el alta.
  - **`INF-07` (`SPEC-INF`)**: Dado un condominio con unidades activas (suma de fracciones $= 1$), gastos del mes $N$ (`EQUAL`, `FRACTION`, `INDIVIDUAL`, `PF1SE`), política mensual y lecturas de gas, cuando se consulta `GET /api/v1/slips/generation/explain?targetMonth=YYYY-MM` y se ejecuta `POST /api/v1/slips/generate`, entonces el importe de cada unidad en los slips generados (`GET /api/v1/slips/{year}/{month}`) coincide céntimo a céntimo con `units[*].totalCents` del explain y la suma coincide con `totals.totalCents`.

### Nivel 7 — Contract, OpenAPI & Container Smoke (`shared` / `contract`)
- **Alcance**:
  - `ContainerSmokeTest`: El contenedor DI de Symfony compila sin errores de cableado ni servicios huérfanos.
  - `OpenApiContractTest`: `GET /api/v1/doc.json` responde `200 OK` con un documento OpenAPI 3.0 válido donde todas las rutas de los 13 Bounded Contexts están descritas por `BoundedContextRouteDescriber`.

### Nivel 8 — Mutation Testing (`mutation`)
- **Alcance**: Ejecutar `vendor/bin/infection` filtrando por la clase de dominio del átomo (`--filter=src/Context/...`).
- **Propósito**: Detectar tests débiles (por ejemplo, un test que verifica que no hubo excepción pero no comprueba el signo del redondeo, el límite exacto `>` vs `>=` en el día 25 o día 5 de `SlipGenerationPolicy`, o el `1e-5` de `IdealFractionSumPolicy`).
- **Umbral Cleanroom**: En átomos de dominio matemático/financiero (`SPEC-INF`, `SPEC-GLM`, `SPEC-UNI`, `SPEC-CIE`), **MSI $\ge 80\%$** y **Covered Code MSI $\ge 85\%$**.

---

## 3. Configuración de Suites en `phpunit.xml.dist`

Para soportar todos los niveles sin fricción y con aislamiento total entre pruebas sin base de datos y pruebas con PostgreSQL:

```xml
<testsuites>
    <testsuite name="unit">
        <directory>tests/Context/*/Domain</directory>
        <directory>tests/Context/*/Application</directory>
    </testsuite>

    <testsuite name="architecture">
        <directory>tests/Architecture</directory>
    </testsuite>

    <testsuite name="shared">
        <directory>tests/Shared/Infrastructure</directory>
    </testsuite>

    <testsuite name="authenticated_integration">
        <directory>tests/Context/*/Infrastructure</directory>
    </testsuite>

    <testsuite name="acceptance">
        <directory>tests/Acceptance</directory>
    </testsuite>
</testsuites>
```
