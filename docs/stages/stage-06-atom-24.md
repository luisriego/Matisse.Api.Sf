# Work Card: `atom-24-cie-02-close-already-closed-month-throws` (CIE-02)

- **Stage**: `stage-06` — SPEC-CIE: Cierre de periodo
- **Spec**: `SPEC-CIE` (`CIE-02`)
- **Level**: `unit`
- **Execution Mode**: `characterization`
- **Branch**: `atom-24-cie-02-close-already-closed-month-throws` (from `stage-06`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: CIE-02
Spec: SPEC-CIE
Nivel: unit
Modo: characterization
Dado: un mes de gasto que ya figura como cerrado en PeriodClosureRepository
Cuando: se evalua PeriodClosureGuard::assertNotClosed o ClosePeriodCommandHandler
Entonces: lanza PeriodAlreadyClosedException
Cubre: src/Context/Slip/Domain/Service/PeriodClosureGuard.php
Test: tests/Context/Slip/Domain/Service/PeriodClosureGuardTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/Slip/Domain/Service/PeriodClosureGuard.php`
- `tests/Context/Slip/Domain/Service/PeriodClosureGuardTest.php`
- `tests/Context/Slip/Application/UseCase/ClosePeriod/ClosePeriodCommandHandlerTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/Slip/Domain/Service/PeriodClosureGuardTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `CIE-02` en `tests/Context/Slip/Domain/Service/PeriodClosureGuardTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Slip/Domain/Service/PeriodClosureGuard.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-06 atom-24-cie-02-close-already-closed-month-throws
   ```
