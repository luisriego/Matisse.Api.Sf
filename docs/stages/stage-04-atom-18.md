# Work Card: `atom-18-mov-05-monthly-recurring-expense-idempotent-once-per-month` (MOV-05)

- **Stage**: `stage-04` — SPEC-MOV: Cuentas, gastos e ingresos
- **Spec**: `SPEC-MOV` (`MOV-05`)
- **Level**: `unit`
- **Execution Mode**: `characterization`
- **Branch**: `atom-18-mov-05-monthly-recurring-expense-idempotent-once-per-month` (from `stage-04`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: MOV-05
Spec: SPEC-MOV
Nivel: unit
Modo: characterization
Dado: un gasto recurrente activo que ya tiene un gasto materializado para el mismo mes
Cuando: se calcula la contribucion de recurrentes con RecurringExpenseSlipContributionService
Entonces: no duplica el importe del gasto recurrente en el pool del mes
Cubre: src/Context/Slip/Domain/Service/RecurringExpenseSlipContributionService.php
Test: tests/Context/Slip/Domain/Service/RecurringExpenseSlipContributionServiceDedupTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/Slip/Domain/Service/RecurringExpenseSlipContributionService.php`
- `tests/Context/Slip/Domain/Service/RecurringExpenseSlipContributionServiceDedupTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/Slip/Domain/Service/RecurringExpenseSlipContributionServiceDedupTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `MOV-05` en `tests/Context/Slip/Domain/Service/RecurringExpenseSlipContributionServiceDedupTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Slip/Domain/Service/RecurringExpenseSlipContributionService.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-04 atom-18-mov-05-monthly-recurring-expense-idempotent-once-per-month
   ```
