# Work Card: `atom-14-mov-01-expense-type-distribution-method-validation` (MOV-01)

- **Stage**: `stage-04` — SPEC-MOV: Cuentas, gastos e ingresos
- **Spec**: `SPEC-MOV` (`MOV-01`)
- **Level**: `unit`
- **Execution Mode**: `characterization`
- **Branch**: `atom-14-mov-01-expense-type-distribution-method-validation` (from `stage-04`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: MOV-01
Spec: SPEC-MOV
Nivel: unit
Modo: characterization
Dado: un metodo de distribucion invalido distinto de EQUAL, FRACTION o INDIVIDUAL
Cuando: se instancia ExpenseTypeDistributionMethod
Entonces: lanza InvalidArgumentException
Cubre: src/Context/Expense/Domain/ValueObject/ExpenseTypeDistributionMethod.php
Test: tests/Context/Expense/Domain/ExpenseTypeAccountRelationTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/Expense/Domain/ValueObject/ExpenseTypeDistributionMethod.php`
- `tests/Context/Expense/Domain/ExpenseTypeAccountRelationTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/Expense/Domain/ExpenseTypeAccountRelationTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `MOV-01` en `tests/Context/Expense/Domain/ExpenseTypeAccountRelationTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Expense/Domain/ValueObject/ExpenseTypeDistributionMethod.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-04 atom-14-mov-01-expense-type-distribution-method-validation
   ```
