# Work Card: `atom-02-inf-02-equal-pool-pico-raises-all-units` (INF-02)

- **Stage**: `stage-01` — SPEC-INF: Informar el valor de cada unidad (Desglose, Picos y Ventana de Generacion)
- **Spec**: `SPEC-INF` (`INF-02`)
- **Level**: `unit`
- **Execution Mode**: `characterization`
- **Branch**: `atom-02-inf-02-equal-pool-pico-raises-all-units` (from `stage-01`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: INF-02
Spec: SPEC-INF
Nivel: unit
Modo: characterization
Dado: 5 unidades activas y un pool igualitario (baseEqualPoolCents) de 103 centimos (parte entera 20, resto 3 > 0)
Cuando: se calcula el desglose con SlipComponentBreakdownService::build
Entonces: cada una de las 5 unidades paga 21 centimos, la suma del pool es 105 centimos (+2 sobre 103) y differenceCents es 0
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
   - Si el modo es `characterization`: escribir o aislar el caso específico de `INF-02` en `tests/Context/Slip/Domain/Service/SlipComponentBreakdownServiceTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Slip/Domain/Service/SlipComponentBreakdownService.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-01 atom-02-inf-02-equal-pool-pico-raises-all-units
   ```
