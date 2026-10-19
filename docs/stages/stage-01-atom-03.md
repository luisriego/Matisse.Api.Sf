# Work Card: `atom-03-inf-03-fraction-pool-pico-raises-all-units` (INF-03)

- **Stage**: `stage-01` — SPEC-INF: Informar el valor de cada unidad (Desglose, Picos y Ventana de Generacion)
- **Spec**: `SPEC-INF` (`INF-03`)
- **Level**: `unit`
- **Execution Mode**: `characterization`
- **Branch**: `atom-03-inf-03-fraction-pool-pico-raises-all-units` (from `stage-01`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: INF-03
Spec: SPEC-INF
Nivel: unit
Modo: characterization
Dado: 5 unidades con fracciones ideales cuyo reparto de fractionPoolCents deja resto > 0 tras truncar la parte entera
Cuando: se calcula el desglose con SlipComponentBreakdownService::build
Entonces: se suma +1 centimo a todas las unidades por igual y nunca solo a las de mayor fraccion ideal
Cubre: src/Context/Slip/Domain/Service/SlipComponentBreakdownService.php
Test: tests/Context/Slip/Domain/Service/SlipComponentBreakdownServiceTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/Slip/Domain/Service/SlipComponentBreakdownService.php`
- `tests/Context/Slip/Domain/Service/SlipComponentBreakdownServiceTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/Slip/Domain/Service/SlipComponentBreakdownServiceTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `INF-03` en `tests/Context/Slip/Domain/Service/SlipComponentBreakdownServiceTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Slip/Domain/Service/SlipComponentBreakdownService.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-01 atom-03-inf-03-fraction-pool-pico-raises-all-units
   ```
