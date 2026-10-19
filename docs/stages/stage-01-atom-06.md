# Work Card: `atom-06-inf-06-force-bypasses-calendar-window` (INF-06)

- **Stage**: `stage-01` — SPEC-INF: Informar el valor de cada unidad (Desglose, Picos y Ventana de Generacion)
- **Spec**: `SPEC-INF` (`INF-06`)
- **Level**: `unit`
- **Execution Mode**: `characterization`
- **Branch**: `atom-06-inf-06-force-bypasses-calendar-window` (from `stage-01`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: INF-06
Spec: SPEC-INF
Nivel: unit
Modo: characterization
Dado: cualquier fecha fuera de la ventana ordinaria (antes del dia 25 o despues del dia 5) con isForced = true
Cuando: se evalua SlipGenerationPolicy::check(year, month, true)
Entonces: no lanza excepcion y permite la operacion
Cubre: src/Context/Slip/Domain/Service/SlipGenerationPolicy.php
Test: tests/Context/Slip/Domain/Service/SlipGenerationPolicyTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/Slip/Domain/Service/SlipGenerationPolicy.php`
- `tests/Context/Slip/Domain/Service/SlipGenerationPolicyTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/Slip/Domain/Service/SlipGenerationPolicyTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `INF-06` en `tests/Context/Slip/Domain/Service/SlipGenerationPolicyTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Slip/Domain/Service/SlipGenerationPolicy.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-01 atom-06-inf-06-force-bypasses-calendar-window
   ```
