# Work Card: `atom-23-cie-01-close-month-without-slips-throws` (CIE-01)

- **Stage**: `stage-06` — SPEC-CIE: Cierre de periodo
- **Spec**: `SPEC-CIE` (`CIE-01`)
- **Level**: `unit`
- **Execution Mode**: `characterization`
- **Branch**: `atom-23-cie-01-close-month-without-slips-throws` (from `stage-06`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: CIE-01
Spec: SPEC-CIE
Nivel: unit
Modo: characterization
Dado: un mes de gasto abierto sin cuotas generadas en el mes de vencimiento N+1
Cuando: se invoca ClosePeriodCommandHandler
Entonces: lanza NoSlipsToCloseException
Cubre: src/Context/Slip/Application/UseCase/ClosePeriod/ClosePeriodCommandHandler.php
Test: tests/Context/Slip/Application/UseCase/ClosePeriod/ClosePeriodCommandHandlerTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/Slip/Application/UseCase/ClosePeriod/ClosePeriodCommandHandler.php`
- `tests/Context/Slip/Application/UseCase/ClosePeriod/ClosePeriodCommandHandlerTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/Slip/Application/UseCase/ClosePeriod/ClosePeriodCommandHandlerTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `CIE-01` en `tests/Context/Slip/Application/UseCase/ClosePeriod/ClosePeriodCommandHandlerTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Slip/Application/UseCase/ClosePeriod/ClosePeriodCommandHandler.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-06 atom-23-cie-01-close-month-without-slips-throws
   ```
