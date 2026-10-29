# Tarjeta de Etapa: stage-11 — Átomo FIX-07 (`atom-44-account-balance-include-compensated-expenses`)

---

## 1. Peregrino & Mochila
- **Objetivo Atómico (SRP)**: FIX-07: Inclusión de ExpenseWasCompensated y re-emisión de ajuste en CompensateExpenseCommandHandler para exactitud de GetAccountBalanceQueryHandler
- **Stage**: `stage-11` — SPEC-FIX-FIN: Integridad Financiera en Generación de Cuotas, Política Mensual y Saldo de Cuentas
- **Spec**: `SPEC-FIX-FIN` (`FIX-07`) | **Nivel**: `unit` | **Modo**: `tdd`
- **Rama dedicada**: `atom-44-account-balance-include-compensated-expenses` (consolidación fast-forward en `stage-11`).
- **TDD Innegociable**: `RED -> GREEN -> REFACTOR -> GATE -> SELLO`.

---

## 2. Botas (Contexto & Especificación)
- **Artefacto principal bajo cobertura**: `src/Context/Account/Application/UseCase/GetAccountBalance/GetAccountBalanceQueryHandler.php`
- **Archivo de prueba objetivo**: `tests/Context/Account/Application/UseCase/GetAccountBalance/GetAccountBalanceQueryHandlerTest.php`
- **Contrato BDD Comprobable**:
  - **Dado (Given)**: una cuenta con saldo inicial 100000, un gasto ingresado de 30000 (ExpenseWasEntered) que luego es compensado (+30000 en ExpenseWasCompensated) y actualizado a 20000
  - **Cuando (When)**: se ejecuta GetAccountBalanceQueryHandler para obtener el saldo de la cuenta
  - **Entonces (Then)**: suma los eventos ExpenseWasCompensated y resta el nuevo ExpenseWasEntered del importe corregido devolviendo el saldo exacto de 80000 centimos
- **Casos de prueba obligatorios en `tests/Context/Account/Application/UseCase/GetAccountBalance/GetAccountBalanceQueryHandlerTest.php`**:
  1. `testAccountBalanceIncludesCompensatedExpenseEvents`: Verifica que cuando la secuencia de eventos de una cuenta tiene `InitialBalanceSet(100000)`, `ExpenseWasEntered(30000)`, `ExpenseWasCompensated(30000)` y `ExpenseWasEntered(20000)`, `GetAccountBalanceQueryHandler` devuelve `80000`.
  2. `testExpenseUpdateAmountRecordsExpenseWasEnteredWhenAccountIsPresent`: Verifica que al compensar y actualizar el importe de un `Expense` con cuenta asociada en `CompensateExpenseCommandHandler`, se registre tanto `ExpenseWasCompensated` como el nuevo `ExpenseWasEntered` con el monto actualizado.

---

## 3. Ropa (Modus Operandi)
1. **Test (RED / Characterization)**:
   - Crea o actualiza `tests/Context/Account/Application/UseCase/GetAccountBalance/GetAccountBalanceQueryHandlerTest.php` con los casos de prueba obligatorios de la sección 2.
   - Ejecuta el oráculo de tests:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/Account/Application/UseCase/GetAccountBalance/GetAccountBalanceQueryHandlerTest.php
     ```
2. **Implementación (GREEN)**:
   - En `src/Context/Account/Application/UseCase/GetAccountBalance/GetAccountBalanceQueryHandler.php`: importar `App\Context\Expense\Domain\Event\ExpenseWasCompensated`, incluir `ExpenseWasCompensated::eventName()` en `findByEventTypesAndOccurredBetween(...)` y en el bucle sumar `$balance += $primitives['amount']` cuando `$domainEvent instanceof ExpenseWasCompensated`.
   - En `src/Context/Expense/Domain/Expense.php`: en `updateAmount(int $newAmount)`, si `$this->account !== null && $newAmount > 0`, registrar `$this->record(new ExpenseWasEntered(...))` con el nuevo importe para que el EventStore refleje el nuevo cargo tras una compensación.
   - Vuelve a ejecutar PHPUnit y verifica verde:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/Account/Application/UseCase/GetAccountBalance/GetAccountBalanceQueryHandlerTest.php
     ```
   - Ejecuta análisis estático y formateador sobre los archivos tocados:
     ```bash
     ./tools/cleanroom/exec_php.sh tools/php-cs-fixer/vendor/bin/php-cs-fixer fix src/Context/Account/Application/UseCase/GetAccountBalance/GetAccountBalanceQueryHandler.php src/Context/Expense/Domain/Expense.php tests/Context/Account/Application/UseCase/GetAccountBalance/GetAccountBalanceQueryHandlerTest.php
     ./tools/cleanroom/exec_php.sh vendor/bin/phpstan analyse src/Context/Account/Application/UseCase/GetAccountBalance/GetAccountBalanceQueryHandler.php src/Context/Expense/Domain/Expense.php tests/Context/Account/Application/UseCase/GetAccountBalance/GetAccountBalanceQueryHandlerTest.php --memory-limit=512M
     ```
3. **Gate & Sello**:
   ```bash
   ./tools/cleanroom/gate.sh stage-11 atom-44-account-balance-include-compensated-expenses --auto-commit
   ```

---

## 4. Mapa con Fronteras (Scope Allowlist)
Solo se permite crear o modificar los siguientes archivos durante este átomo (cualquier archivo fuera de esta lista abortará el `Gate 0`):
- `src/Context/Account/Application/UseCase/GetAccountBalance/GetAccountBalanceQueryHandler.php`
- `src/Context/Expense/Domain/Expense.php`
- `src/Context/Expense/Application/UseCase/CompensateExpense/CompensateExpenseCommandHandler.php`
- `tests/Context/Account/Application/UseCase/GetAccountBalance/GetAccountBalanceQueryHandlerTest.php`

---

## 5. Silbato & Libro de Frases
- Si el oráculo de `gate.sh` falla **3 veces consecutivas** o supera **10 minutos** de bucle en la misma hipótesis -> el circuit breaker registrará `STATUS: BLOCKED` en `docs/stages/log.md`.
- Al completar con éxito el Gate (código de salida `0`), emite bloque YAML final con `STATUS: SEALED`:
```yaml
ATOM: atom-44-account-balance-include-compensated-expenses
STAGE: stage-11
SPEC: FIX-07
STATUS: SEALED
```
