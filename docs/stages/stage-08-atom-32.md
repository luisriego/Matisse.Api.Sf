# Work Card: `atom-32-ind-03-openapi-and-security-boundary-contracts` (IND-03)

- **Stage**: `stage-08` — TESTING-STANDARD-INDUSTRIA: Higiene de Suites, Arquitectura Hexagonal y Contratos OpenAPI
- **Spec**: `TESTING-STANDARD-INDUSTRIA` (`IND-03`)
- **Level**: `integration`
- **Execution Mode**: `tdd`
- **Branch**: `atom-32-ind-03-openapi-and-security-boundary-contracts` (from `stage-08`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: IND-03
Spec: TESTING-STANDARD-INDUSTRIA
Nivel: integration
Modo: tdd
Dado: el kernel de Symfony con NelmioApiDocBundle y los 13 Bounded Contexts registrados
Cuando: se solicita GET /api/v1/doc.json en OpenApiContractTest
Entonces: responde 200 OK con un documento OpenAPI 3.0.x valido y todas las rutas /api/v1/* documentadas y etiquetadas
Cubre: src/Shared/Infrastructure/OpenApi/BoundedContextRouteDescriber.php
Test: tests/Shared/Infrastructure/OpenApiContractTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Shared/Infrastructure/OpenApi/BoundedContextRouteDescriber.php`
- `tests/Shared/Infrastructure/OpenApiContractTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Shared/Infrastructure/OpenApiContractTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `IND-03` en `tests/Shared/Infrastructure/OpenApiContractTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Shared/Infrastructure/OpenApi/BoundedContextRouteDescriber.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-08 atom-32-ind-03-openapi-and-security-boundary-contracts
   ```
