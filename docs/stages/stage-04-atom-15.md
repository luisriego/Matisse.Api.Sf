# Work Card: `atom-15-mov-02-individual-expense-excluded-from-common-pool` (MOV-02)

- **Stage**: `stage-04` — SPEC-MOV: Cuentas, gastos e ingresos
- **Spec**: `SPEC-MOV` (`MOV-02`)
- **Level**: `unit`
- **Execution Mode**: `characterization`
- **Branch**: `atom-15-mov-02-individual-expense-excluded-from-common-pool` (from `stage-04`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: MOV-02
Spec: SPEC-MOV
Nivel: unit
Modo: characterization
Dado: un conjunto de gastos del mes que incluye gastos INDIVIDUAL junto con gastos EQUAL y FRACTION
Cuando: se agrega con MonthlyExpenseAggregatorService::aggregateTotals
Entonces: los gastos INDIVIDUAL solo suman en individual/individualByUnit y nunca en equal ni fraction
Cubre: src/Context/Slip/Domain/Service/MonthlyExpenseAggregatorService.php
Test: tests/Context/Slip/Domain/Service/MonthlyExpenseAggregatorServiceTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/Slip/Domain/Service/MonthlyExpenseAggregatorService.php`
- `tests/Context/Slip/Domain/Service/MonthlyExpenseAggregatorServiceTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/Slip/Domain/Service/MonthlyExpenseAggregatorServiceTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `MOV-02` en `tests/Context/Slip/Domain/Service/MonthlyExpenseAggregatorServiceTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Slip/Domain/Service/MonthlyExpenseAggregatorService.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-04 atom-15-mov-02-individual-expense-excluded-from-common-pool
   ```
