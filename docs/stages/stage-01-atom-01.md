# Work Card: `atom-01-inf-01-equal-pool-exact-division` (INF-01)

- **Stage**: `stage-01` — SPEC-INF: Informar el valor de cada unidad (Desglose, Picos y Ventana de Generacion)
- **Spec**: `SPEC-INF` (`INF-01`)
- **Level**: `unit`
- **Execution Mode**: `characterization`
- **Branch**: `atom-01-inf-01-equal-pool-exact-division` (from `stage-01`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: INF-01
Spec: SPEC-INF
Nivel: unit
Modo: characterization
Dado: 5 unidades activas (101, 201, 301, 401, 501) y un pool igualitario (baseEqualPoolCents) de 100 centimos con el resto de pools en 0
Cuando: se calcula el desglose con SlipComponentBreakdownService::build
Entonces: cada unidad paga exactamente 20 centimos y la suma total del pool es 100 centimos sin diferencia
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
   - Si el modo es `characterization`: escribir o aislar el caso específico de `INF-01` en `tests/Context/Slip/Domain/Service/SlipComponentBreakdownServiceTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Slip/Domain/Service/SlipComponentBreakdownService.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-01 atom-01-inf-01-equal-pool-exact-division
   ```
