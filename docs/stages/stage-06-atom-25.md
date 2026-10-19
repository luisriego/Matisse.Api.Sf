# Work Card: `atom-25-cie-03-generate-slips-after-period-closed-fails-integration` (CIE-03)

- **Stage**: `stage-06` — SPEC-CIE: Cierre de periodo
- **Spec**: `SPEC-CIE` (`CIE-03`)
- **Level**: `integration`
- **Execution Mode**: `tdd`
- **Branch**: `atom-25-cie-03-generate-slips-after-period-closed-fails-integration` (from `stage-06`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: CIE-03
Spec: SPEC-CIE
Nivel: integration
Modo: tdd
Dado: un mes con cuotas generadas que ha sido cerrado mediante POST /api/v1/slips/close-period
Cuando: se intenta regenerar cuotas para ese mismo mes mediante POST /api/v1/slips/generate
Entonces: la API rechaza la operacion porque el periodo ya esta cerrado y las cuotas quedan intactas
Cubre: src/Context/Slip/Infrastructure/Http/Controller/ClosePeriodPostController.php
Test: tests/Context/Slip/Infrastructure/Http/Controller/ClosePeriodAndGenerationGuardControllerTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/Slip/Infrastructure/Http/Controller/ClosePeriodPostController.php`
- `src/Context/Slip/Infrastructure/Http/Controller/SlipGenerationPostController.php`
- `tests/Context/Slip/Infrastructure/Http/Controller/ClosePeriodAndGenerationGuardControllerTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/Slip/Infrastructure/Http/Controller/ClosePeriodAndGenerationGuardControllerTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `CIE-03` en `tests/Context/Slip/Infrastructure/Http/Controller/ClosePeriodAndGenerationGuardControllerTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Slip/Infrastructure/Http/Controller/ClosePeriodPostController.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-06 atom-25-cie-03-generate-slips-after-period-closed-fails-integration
   ```
