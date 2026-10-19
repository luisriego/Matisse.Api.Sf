# Work Card: `atom-28-lec-01-ledger-empty-month-returns-empty-list` (LEC-01)

- **Stage**: `stage-07` — SPEC-BAN y SPEC-LEC: Extracto OFX, Prevision y Libro
- **Spec**: `SPEC-BAN, SPEC-LEC` (`LEC-01`)
- **Level**: `integration`
- **Execution Mode**: `tdd`
- **Branch**: `atom-28-lec-01-ledger-empty-month-returns-empty-list` (from `stage-07`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: LEC-01
Spec: SPEC-BAN, SPEC-LEC
Nivel: integration
Modo: tdd
Dado: un mes sin gastos ni ingresos registrados
Cuando: se consulta GET /api/v1/ledger/movements?year={Y}&month={M}
Entonces: responde 200 OK con month=YYYY-MM y movements=[]
Cubre: src/Context/Ledger/Infrastructure/Http/Controller/LedgerMovementsGetController.php
Test: tests/Context/Ledger/Infrastructure/Http/Controller/LedgerMovementsGetControllerTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/Ledger/Infrastructure/Http/Controller/LedgerMovementsGetController.php`
- `tests/Context/Ledger/Infrastructure/Http/Controller/LedgerMovementsGetControllerTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/Ledger/Infrastructure/Http/Controller/LedgerMovementsGetControllerTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `LEC-01` en `tests/Context/Ledger/Infrastructure/Http/Controller/LedgerMovementsGetControllerTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Ledger/Infrastructure/Http/Controller/LedgerMovementsGetController.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-07 atom-28-lec-01-ledger-empty-month-returns-empty-list
   ```
