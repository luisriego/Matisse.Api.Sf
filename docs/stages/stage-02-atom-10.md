# Work Card: `atom-10-ten-03-finalize-setup-idempotent` (TEN-03)

- **Stage**: `stage-02` — SPEC-TEN: Un condominio y un sindico
- **Spec**: `SPEC-TEN` (`TEN-03`)
- **Level**: `acceptance`
- **Execution Mode**: `characterization`
- **Branch**: `atom-10-ten-03-finalize-setup-idempotent` (from `stage-02`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: TEN-03
Spec: SPEC-TEN
Nivel: acceptance
Modo: characterization
Dado: un condominio cuyo setup inicial ya ha sido finalizado mediante POST /api/v1/setup/finalize
Cuando: se vuelve a invocar POST /api/v1/setup/finalize y se consulta GET /api/v1/setup/status
Entonces: responde 200 OK con alreadyFinalized=true sin duplicar eventos y status mantiene isFinalized=true
Cubre: src/Context/Setup/Infrastructure/Http/Controller/SetupFinalizePostController.php
Test: tests/Context/Setup/Infrastructure/Http/Controller/SetupFinalizePostControllerTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/Setup/Infrastructure/Http/Controller/SetupFinalizePostController.php`
- `src/Context/Setup/Application/Service/SetupFinalizationService.php`
- `tests/Context/Setup/Infrastructure/Http/Controller/SetupFinalizePostControllerTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/Setup/Infrastructure/Http/Controller/SetupFinalizePostControllerTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `TEN-03` en `tests/Context/Setup/Infrastructure/Http/Controller/SetupFinalizePostControllerTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Setup/Infrastructure/Http/Controller/SetupFinalizePostController.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-02 atom-10-ten-03-finalize-setup-idempotent
   ```
