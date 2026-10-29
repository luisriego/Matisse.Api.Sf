# Tarjeta de Etapa: stage-12 — Átomo FIX-09 (`atom-46-enforce-period-closure-guard-on-expenses-and-incomes`)

---

## 1. Peregrino & Mochila
- **Objetivo Atómico (SRP)**: FIX-09: Inyección de PeriodClosureGuard en los CommandHandlers de mutación de Gastos e Ingresos para impedir escrituras en meses cerrados
- **Stage**: `stage-12` — SPEC-FIX-GUARD: Idempotencia OFX Real y Guardas Transversales de Cierre de Periodo
- **Spec**: `SPEC-FIX-GUARD` (`FIX-09`) | **Nivel**: `unit` | **Modo**: `tdd`
- **Rama dedicada**: `atom-46-enforce-period-closure-guard-on-expenses-and-incomes` (consolidación fast-forward en `stage-12`).
- **TDD Innegociable**: `RED -> GREEN -> REFACTOR -> GATE -> SELLO`.

---

## 2. Botas (Contexto & Especificación)
- **Artefacto principal bajo cobertura**: `src/Context/Expense/Application/UseCase/EnterExpense/EnterExpenseCommandHandler.php`
- **Archivo de prueba objetivo**: `tests/Context/Expense/Application/UseCase/EnterExpense/PeriodClosureExpenseIncomeGuardTest.php`
- **Contrato BDD Comprobable**:
  - **Dado (Given)**: un periodo contable (año, mes) que figura como cerrado en PeriodClosureGuard
  - **Cuando (When)**: se intenta registrar o modificar un gasto o un ingreso cuya fecha de vencimiento pertenece a ese mes cerrado
  - **Entonces (Then)**: EnterExpenseCommandHandler, UpdateExpenseCommandHandler, CompensateExpenseCommandHandler, EnterIncomeCommandHandler y UpdateIncomeCommandHandler lanzan PeriodAlreadyClosedException antes de persistir cambios
- **Casos de prueba obligatorios en `tests/Context/Expense/Application/UseCase/EnterExpense/PeriodClosureExpenseIncomeGuardTest.php`**:
  1. `testEnterExpenseOnClosedPeriodThrowsPeriodAlreadyClosedException`: Configura `PeriodClosureRepository::isClosed(2026, 3) => true` y verifica que `EnterExpenseCommandHandler` lance `PeriodAlreadyClosedException` al intentar crear un gasto en `2026-03-15`.
  2. `testEnterIncomeOnClosedPeriodThrowsPeriodAlreadyClosedException`: Verifica que `EnterIncomeCommandHandler` lance `PeriodAlreadyClosedException` al intentar registrar un ingreso con `dueDate` en `2026-03-10`.
  3. `testUpdateExpenseAndUpdateIncomeOnClosedPeriodThrowPeriodAlreadyClosedException`: Verifica que `UpdateExpenseCommandHandler` y `UpdateIncomeCommandHandler` lancen `PeriodAlreadyClosedException` cuando el periodo está cerrado.

---

## 3. Ropa (Modus Operandi)
1. **Test (RED / Characterization)**:
   - Crea o actualiza `tests/Context/Expense/Application/UseCase/EnterExpense/PeriodClosureExpenseIncomeGuardTest.php` con los casos de prueba obligatorios de la sección 2.
   - Ejecuta el oráculo de tests:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/Expense/Application/UseCase/EnterExpense/PeriodClosureExpenseIncomeGuardTest.php
     ```
2. **Implementación (GREEN)**:
   - En `EnterExpenseCommandHandler`, `UpdateExpenseCommandHandler`, `CompensateExpenseCommandHandler`, `EnterIncomeCommandHandler` y `UpdateIncomeCommandHandler`: añadir `?PeriodClosureGuard $periodClosureGuard = null` al final del constructor (manteniendo compatibilidad hacia atrás con tests existentes que instancian el handler sin ese argumento).
   - En `__invoke` de cada handler: extraer año y mes de la fecha de vencimiento (`$dueDate`) y llamar a `$this->periodClosureGuard?->assertNotClosed((int) $date->format('Y'), (int) $date->format('n'))`.
   - Vuelve a ejecutar PHPUnit y verifica verde:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/Expense/Application/UseCase/EnterExpense/PeriodClosureExpenseIncomeGuardTest.php
     ```
   - Ejecuta análisis estático y formateador sobre los archivos tocados:
     ```bash
     ./tools/cleanroom/exec_php.sh tools/php-cs-fixer/vendor/bin/php-cs-fixer fix src/Context/Expense/Application/UseCase/EnterExpense/EnterExpenseCommandHandler.php src/Context/Expense/Application/UseCase/UpdateExpense/UpdateExpenseCommandHandler.php src/Context/Expense/Application/UseCase/CompensateExpense/CompensateExpenseCommandHandler.php src/Context/Income/Application/UseCase/EnterIncome/EnterIncomeCommandHandler.php src/Context/Income/Application/UseCase/UpdateIncome/UpdateIncomeCommandHandler.php tests/Context/Expense/Application/UseCase/EnterExpense/PeriodClosureExpenseIncomeGuardTest.php
     ./tools/cleanroom/exec_php.sh vendor/bin/phpstan analyse src/Context/Expense/Application/UseCase/EnterExpense/EnterExpenseCommandHandler.php src/Context/Expense/Application/UseCase/UpdateExpense/UpdateExpenseCommandHandler.php src/Context/Expense/Application/UseCase/CompensateExpense/CompensateExpenseCommandHandler.php src/Context/Income/Application/UseCase/EnterIncome/EnterIncomeCommandHandler.php src/Context/Income/Application/UseCase/UpdateIncome/UpdateIncomeCommandHandler.php tests/Context/Expense/Application/UseCase/EnterExpense/PeriodClosureExpenseIncomeGuardTest.php --memory-limit=512M
     ```
3. **Gate & Sello**:
   ```bash
   ./tools/cleanroom/gate.sh stage-12 atom-46-enforce-period-closure-guard-on-expenses-and-incomes --auto-commit
   ```

---

## 4. Mapa con Fronteras (Scope Allowlist)
Solo se permite crear o modificar los siguientes archivos durante este átomo (cualquier archivo fuera de esta lista abortará el `Gate 0`):
- `src/Context/Expense/Application/UseCase/EnterExpense/EnterExpenseCommandHandler.php`
- `src/Context/Expense/Application/UseCase/UpdateExpense/UpdateExpenseCommandHandler.php`
- `src/Context/Expense/Application/UseCase/CompensateExpense/CompensateExpenseCommandHandler.php`
- `src/Context/Income/Application/UseCase/EnterIncome/EnterIncomeCommandHandler.php`
- `src/Context/Income/Application/UseCase/UpdateIncome/UpdateIncomeCommandHandler.php`
- `tests/Context/Expense/Application/UseCase/EnterExpense/PeriodClosureExpenseIncomeGuardTest.php`

---

## 5. Silbato & Libro de Frases
- Si el oráculo de `gate.sh` falla **3 veces consecutivas** o supera **10 minutos** de bucle en la misma hipótesis -> el circuit breaker registrará `STATUS: BLOCKED` en `docs/stages/log.md`.
- Al completar con éxito el Gate (código de salida `0`), emite bloque YAML final con `STATUS: SEALED`:
```yaml
ATOM: atom-46-enforce-period-closure-guard-on-expenses-and-incomes
STAGE: stage-12
SPEC: FIX-09
STATUS: SEALED
```
