# Work Card: `atom-11-uni-01-fraction-sum-within-tolerance-accepted` (UNI-01)

- **Stage**: `stage-03` — SPEC-UNI: Unidades y fraccion ideal
- **Spec**: `SPEC-UNI` (`UNI-01`)
- **Level**: `unit`
- **Execution Mode**: `characterization`
- **Branch**: `atom-11-uni-01-fraction-sum-within-tolerance-accepted` (from `stage-03`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: UNI-01
Spec: SPEC-UNI
Nivel: unit
Modo: characterization
Dado: una suma de fracciones ideales acumulada de 1.00000005 (menor o igual a 1.0 + 1e-5)
Cuando: se evalua IdealFractionSumPolicy::exceedsMaximum(1.00000005)
Entonces: devuelve false (isWithinMaximum devuelve true) y permite la operacion
Cubre: src/Context/ResidentUnit/Domain/IdealFractionSumPolicy.php
Test: tests/Context/ResidentUnit/Domain/IdealFractionSumPolicyTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/ResidentUnit/Domain/IdealFractionSumPolicy.php`
- `tests/Context/ResidentUnit/Domain/IdealFractionSumPolicyTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/ResidentUnit/Domain/IdealFractionSumPolicyTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `UNI-01` en `tests/Context/ResidentUnit/Domain/IdealFractionSumPolicyTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/ResidentUnit/Domain/IdealFractionSumPolicy.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-03 atom-11-uni-01-fraction-sum-within-tolerance-accepted
   ```
