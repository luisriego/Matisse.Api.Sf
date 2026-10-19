# Work Card: `atom-26-ban-01-bank-import-line-xor-expense-income` (BAN-01)

- **Stage**: `stage-07` — SPEC-BAN y SPEC-LEC: Extracto OFX, Prevision y Libro
- **Spec**: `SPEC-BAN, SPEC-LEC` (`BAN-01`)
- **Level**: `unit`
- **Execution Mode**: `tdd`
- **Branch**: `atom-26-ban-01-bank-import-line-xor-expense-income` (from `stage-07`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: BAN-01
Spec: SPEC-BAN, SPEC-LEC
Nivel: unit
Modo: tdd
Dado: parametros para construir una entidad BankTransactionImport
Cuando: se intenta construir con ambos nulos (null, null) o ambos informados (expenseId, incomeId)
Entonces: lanza InvalidArgumentException garantizando la invariante XOR
Cubre: src/Context/BankStatement/Domain/BankTransactionImport.php
Test: tests/Context/BankStatement/Domain/BankTransactionImportTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/BankStatement/Domain/BankTransactionImport.php`
- `tests/Context/BankStatement/Domain/BankTransactionImportTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/BankStatement/Domain/BankTransactionImportTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `BAN-01` en `tests/Context/BankStatement/Domain/BankTransactionImportTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/BankStatement/Domain/BankTransactionImport.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-07 atom-26-ban-01-bank-import-line-xor-expense-income
   ```
