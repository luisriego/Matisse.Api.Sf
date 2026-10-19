# Work Card: `atom-30-ind-01-suite-isolation-and-phpunit-tiers` (IND-01)

- **Stage**: `stage-08` — TESTING-STANDARD-INDUSTRIA: Higiene de Suites, Arquitectura Hexagonal y Contratos OpenAPI
- **Spec**: `TESTING-STANDARD-INDUSTRIA` (`IND-01`)
- **Level**: `unit`
- **Execution Mode**: `tdd`
- **Branch**: `atom-30-ind-01-suite-isolation-and-phpunit-tiers` (from `stage-08`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: IND-01
Spec: TESTING-STANDARD-INDUSTRIA
Nivel: unit
Modo: tdd
Dado: CatalogSeederTest ubicado bajo Application/ extendiendo ApiTestCase y phpunit.xml.dist sin suites architecture ni acceptance
Cuando: se convierte CatalogSeederTest en unitario puro (y se mueve la prueba DB a Infrastructure) y se actualizan las suites en phpunit.xml.dist
Entonces: phpunit --testsuite=unit ejecuta el 100% de los tests sin necesitar conexion a PostgreSQL
Cubre: phpunit.xml.dist
Test: tests/Context/Setup/Application/Service/CatalogSeederTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `phpunit.xml.dist`
- `tests/Context/Setup/Application/Service/CatalogSeederTest.php`
- `tests/Context/Setup/Infrastructure/Persistence/CatalogSeederIntegrationTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/Setup/Application/Service/CatalogSeederTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `IND-01` en `tests/Context/Setup/Application/Service/CatalogSeederTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `phpunit.xml.dist` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-08 atom-30-ind-01-suite-isolation-and-phpunit-tiers
   ```
