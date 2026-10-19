# Work Card: `atom-13-uni-03-create-and-patch-unit-fraction-persists` (UNI-03)

- **Stage**: `stage-03` — SPEC-UNI: Unidades y fraccion ideal
- **Spec**: `SPEC-UNI` (`UNI-03`)
- **Level**: `integration`
- **Execution Mode**: `characterization`
- **Branch**: `atom-13-uni-03-create-and-patch-unit-fraction-persists` (from `stage-03`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: UNI-03
Spec: SPEC-UNI
Nivel: integration
Modo: characterization
Dado: una unidad residencial creada en base de datos
Cuando: el sindico envia PATCH /api/v1/resident-unit/{id}/ideal-fraction y luego consulta la unidad
Entonces: la nueva fraccion ideal queda persistida y se devuelve en la lectura
Cubre: src/Context/ResidentUnit/Infrastructure/Http/Controller/ResidentUnitIdealFractionPatchController.php
Test: tests/Context/ResidentUnit/Infrastructure/Http/Controller/ResidentUnitIdealFractionPatchControllerTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/ResidentUnit/Infrastructure/Http/Controller/ResidentUnitIdealFractionPatchController.php`
- `tests/Context/ResidentUnit/Infrastructure/Http/Controller/ResidentUnitIdealFractionPatchControllerTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/ResidentUnit/Infrastructure/Http/Controller/ResidentUnitIdealFractionPatchControllerTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `UNI-03` en `tests/Context/ResidentUnit/Infrastructure/Http/Controller/ResidentUnitIdealFractionPatchControllerTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/ResidentUnit/Infrastructure/Http/Controller/ResidentUnitIdealFractionPatchController.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-03 atom-13-uni-03-create-and-patch-unit-fraction-persists
   ```
