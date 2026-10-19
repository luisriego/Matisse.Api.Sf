# Work Card: `atom-21-glm-03-pf1se-splits-60000-syndic-and-remainder-to-unit-401` (GLM-03)

- **Stage**: `stage-05` — SPEC-GLM: Gas y politica del mes
- **Spec**: `SPEC-GLM` (`GLM-03`)
- **Level**: `unit`
- **Execution Mode**: `characterization`
- **Branch**: `atom-21-glm-03-pf1se-splits-60000-syndic-and-remainder-to-unit-401` (from `stage-05`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: GLM-03
Spec: SPEC-GLM
Nivel: unit
Modo: characterization
Dado: un gasto PF1SE de 67000 centimos y unidades activas incluyendo la unidad 401
Cuando: se ejecuta SyndicFeeSlipPoolAdjustmentService::adjust
Entonces: asigna 60000 al pool igual de sindico y 7000 como gasto individual de la unidad 401
Cubre: src/Context/Slip/Domain/Service/SyndicFeeSlipPoolAdjustmentService.php
Test: tests/Context/Slip/Domain/Service/SyndicFeeSlipPoolAdjustmentServiceTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/Slip/Domain/Service/SyndicFeeSlipPoolAdjustmentService.php`
- `tests/Context/Slip/Domain/Service/SyndicFeeSlipPoolAdjustmentServiceTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/Slip/Domain/Service/SyndicFeeSlipPoolAdjustmentServiceTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `GLM-03` en `tests/Context/Slip/Domain/Service/SyndicFeeSlipPoolAdjustmentServiceTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Slip/Domain/Service/SyndicFeeSlipPoolAdjustmentService.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-05 atom-21-glm-03-pf1se-splits-60000-syndic-and-remainder-to-unit-401
   ```
