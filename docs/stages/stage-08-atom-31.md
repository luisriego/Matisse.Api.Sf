# Work Card: `atom-31-ind-02-hexagonal-architecture-fitness-tests` (IND-02)

- **Stage**: `stage-08` — TESTING-STANDARD-INDUSTRIA: Higiene de Suites, Arquitectura Hexagonal y Contratos OpenAPI
- **Spec**: `TESTING-STANDARD-INDUSTRIA` (`IND-02`)
- **Level**: `architecture`
- **Execution Mode**: `tdd`
- **Branch**: `atom-31-ind-02-hexagonal-architecture-fitness-tests` (from `stage-08`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: IND-02
Spec: TESTING-STANDARD-INDUSTRIA
Nivel: architecture
Modo: tdd
Dado: todos los archivos PHP bajo src/Context/*/Domain y src/Context/*/Application
Cuando: se ejecuta tests/Architecture/HexagonalArchitectureTest.php
Entonces: verifica que Domain no importa Application/Infrastructure/Doctrine/HttpFoundation, Application no importa Infrastructure y los eventos de dominio son readonly
Cubre: src/Shared/Domain/Aggregate/AggregateRoot.php
Test: tests/Architecture/HexagonalArchitectureTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `phpunit.xml.dist`
- `tests/Architecture/HexagonalArchitectureTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Architecture/HexagonalArchitectureTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `IND-02` en `tests/Architecture/HexagonalArchitectureTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Shared/Domain/Aggregate/AggregateRoot.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-08 atom-31-ind-02-hexagonal-architecture-fitness-tests
   ```
