# Work Card: `atom-16-mov-03-put-expenses-enter-returns-201-with-body` (MOV-03)

- **Stage**: `stage-04` — SPEC-MOV: Cuentas, gastos e ingresos
- **Spec**: `SPEC-MOV` (`MOV-03`)
- **Level**: `integration`
- **Execution Mode**: `characterization`
- **Branch**: `atom-16-mov-03-put-expenses-enter-returns-201-with-body` (from `stage-04`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: MOV-03
Spec: SPEC-MOV
Nivel: integration
Modo: characterization
Dado: una cuenta activa y un tipo de gasto validos
Cuando: se envia PUT /api/v1/expenses/enter con un gasto nuevo
Entonces: responde 201 Created con los datos del gasto en el cuerpo JSON
Cubre: src/Context/Expense/Infrastructure/Http/Controller/ExpenseEnterPutController.php
Test: tests/Context/Expense/Infrastructure/Http/Controller/ExpenseEnterPutControllerTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/Expense/Infrastructure/Http/Controller/ExpenseEnterPutController.php`
- `tests/Context/Expense/Infrastructure/Http/Controller/ExpenseEnterPutControllerTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/Expense/Infrastructure/Http/Controller/ExpenseEnterPutControllerTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `MOV-03` en `tests/Context/Expense/Infrastructure/Http/Controller/ExpenseEnterPutControllerTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Expense/Infrastructure/Http/Controller/ExpenseEnterPutController.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-04 atom-16-mov-03-put-expenses-enter-returns-201-with-body
   ```
