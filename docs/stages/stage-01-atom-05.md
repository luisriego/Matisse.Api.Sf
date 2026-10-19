# Work Card: `atom-05-inf-05-recreation-after-day-5-rejected` (INF-05)

- **Stage**: `stage-01` — SPEC-INF: Informar el valor de cada unidad (Desglose, Picos y Ventana de Generacion)
- **Spec**: `SPEC-INF` (`INF-05`)
- **Level**: `unit`
- **Execution Mode**: `characterization`
- **Branch**: `atom-05-inf-05-recreation-after-day-5-rejected` (from `stage-01`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: INF-05
Spec: SPEC-INF
Nivel: unit
Modo: characterization
Dado: ya existen cuotas para el mes de vencimiento N+1, isForced es false y la fecha actual es posterior al dia 5 del mes N+1
Cuando: se evalua SlipGenerationPolicy::check(year, month, false)
Entonces: lanza RecreationExpiredException
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
   - Si el modo es `characterization`: escribir o aislar el caso específico de `INF-05` en `tests/Context/Slip/Domain/Service/SlipGenerationPolicyTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Slip/Domain/Service/SlipGenerationPolicy.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-01 atom-05-inf-05-recreation-after-day-5-rejected
   ```
