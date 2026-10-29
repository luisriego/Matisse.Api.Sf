# Tarjeta de Etapa: stage-12 — Átomo FIX-08 (`atom-45-wire-bank-transaction-import-idempotency`)

---

## 1. Peregrino & Mochila
- **Objetivo Atómico (SRP)**: FIX-08: Persistencia y verificación de idempotencia por importLineKey en ConfirmBankOfxLinesCommandHandler mediante BankTransactionImportRepository
- **Stage**: `stage-12` — SPEC-FIX-GUARD: Idempotencia OFX Real y Guardas Transversales de Cierre de Periodo
- **Spec**: `SPEC-FIX-GUARD` (`FIX-08`) | **Nivel**: `unit` | **Modo**: `tdd`
- **Rama dedicada**: `atom-45-wire-bank-transaction-import-idempotency` (consolidación fast-forward en `stage-12`).
- **TDD Innegociable**: `RED -> GREEN -> REFACTOR -> GATE -> SELLO`.

---

## 2. Botas (Contexto & Especificación)
- **Artefacto principal bajo cobertura**: `src/Context/BankStatement/Application/UseCase/ConfirmBankOfxLines/ConfirmBankOfxLinesCommandHandler.php`
- **Archivo de prueba objetivo**: `tests/Context/BankStatement/Application/UseCase/ConfirmBankOfxLines/ConfirmBankOfxLinesIdempotencyTest.php`
- **Contrato BDD Comprobable**:
  - **Dado (Given)**: líneas OFX con importLineKey enviadas a ConfirmBankOfxLinesCommandHandler y un BankTransactionImportRepository
  - **Cuando (When)**: se confirma una línea OFX por primera vez y posteriormente se reenvía la misma línea con el mismo importLineKey y bankAccountId
  - **Entonces (Then)**: en la primera confirmación persiste BankTransactionImport en el repositorio y en la segunda omite la línea ya importada sin volver a despachar EnterExpenseCommand ni EnterIncomeCommand
- **Casos de prueba obligatorios en `tests/Context/BankStatement/Application/UseCase/ConfirmBankOfxLines/ConfirmBankOfxLinesIdempotencyTest.php`**:
  1. `testPersistsBankTransactionImportOnFirstConfirmation`: Verifica que al confirmar una línea de gasto o crédito con `importLineKey`, `ConfirmBankOfxLinesCommandHandler` invoque `BankTransactionImportRepository::save` con la entidad `BankTransactionImport` correspondiente.
  2. `testSkipsAlreadyImportedLineKeyOnDuplicateConfirmation`: Configura `BankTransactionImportRepository::existsByImportLineKey` devolviendo `true` para una línea y verifica que `ConfirmBankOfxLinesCommandHandler` no despache comandos al bus para esa línea y devuelva `imported=0`.

---

## 3. Ropa (Modus Operandi)
1. **Test (RED / Characterization)**:
   - Crea o actualiza `tests/Context/BankStatement/Application/UseCase/ConfirmBankOfxLines/ConfirmBankOfxLinesIdempotencyTest.php` con los casos de prueba obligatorios de la sección 2.
   - Ejecuta el oráculo de tests:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/BankStatement/Application/UseCase/ConfirmBankOfxLines/ConfirmBankOfxLinesIdempotencyTest.php
     ```
2. **Implementación (GREEN)**:
   - En `src/Context/BankStatement/Application/UseCase/ConfirmBankOfxLines/ConfirmBankOfxLinesCommandHandler.php`: añadir `?BankTransactionImportRepository $bankTransactionImportRepository = null` al constructor.
   - Antes de procesar cada línea en `expenses` y `otherCredits`, si `$this->bankTransactionImportRepository !== null && $line->importLineKey !== '' && $this->bankTransactionImportRepository->existsByImportLineKey($line->importLineKey, $command->bankAccountId)`, omitir la línea.
   - Tras despachar el gasto o ingreso, si `$this->bankTransactionImportRepository !== null && $line->importLineKey !== ''`, persistir `BankTransactionImport::forExpense(...)` o `BankTransactionImport::forIncome(...)` mediante `$this->bankTransactionImportRepository->save(...)`.
   - Vuelve a ejecutar PHPUnit y verifica verde:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/BankStatement/Application/UseCase/ConfirmBankOfxLines/ConfirmBankOfxLinesIdempotencyTest.php
     ```
   - Ejecuta análisis estático y formateador sobre los archivos tocados:
     ```bash
     ./tools/cleanroom/exec_php.sh tools/php-cs-fixer/vendor/bin/php-cs-fixer fix src/Context/BankStatement/Application/UseCase/ConfirmBankOfxLines/ConfirmBankOfxLinesCommandHandler.php tests/Context/BankStatement/Application/UseCase/ConfirmBankOfxLines/ConfirmBankOfxLinesIdempotencyTest.php
     ./tools/cleanroom/exec_php.sh vendor/bin/phpstan analyse src/Context/BankStatement/Application/UseCase/ConfirmBankOfxLines/ConfirmBankOfxLinesCommandHandler.php tests/Context/BankStatement/Application/UseCase/ConfirmBankOfxLines/ConfirmBankOfxLinesIdempotencyTest.php --memory-limit=512M
     ```
3. **Gate & Sello**:
   ```bash
   ./tools/cleanroom/gate.sh stage-12 atom-45-wire-bank-transaction-import-idempotency --auto-commit
   ```

---

## 4. Mapa con Fronteras (Scope Allowlist)
Solo se permite crear o modificar los siguientes archivos durante este átomo (cualquier archivo fuera de esta lista abortará el `Gate 0`):
- `src/Context/BankStatement/Application/UseCase/ConfirmBankOfxLines/ConfirmBankOfxLinesCommandHandler.php`
- `tests/Context/BankStatement/Application/UseCase/ConfirmBankOfxLines/ConfirmBankOfxLinesCommandHandlerTest.php`
- `tests/Context/BankStatement/Application/UseCase/ConfirmBankOfxLines/ConfirmBankOfxLinesIdempotencyTest.php`

---

## 5. Silbato & Libro de Frases
- Si el oráculo de `gate.sh` falla **3 veces consecutivas** o supera **10 minutos** de bucle en la misma hipótesis -> el circuit breaker registrará `STATUS: BLOCKED` en `docs/stages/log.md`.
- Al completar con éxito el Gate (código de salida `0`), emite bloque YAML final con `STATUS: SEALED`:
```yaml
ATOM: atom-45-wire-bank-transaction-import-idempotency
STAGE: stage-12
SPEC: FIX-08
STATUS: SEALED
```
