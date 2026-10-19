# Work Card: `atom-17-mov-04-enter-income-listed-in-month-query` (MOV-04)

- **Stage**: `stage-04` — SPEC-MOV: Cuentas, gastos e ingresos
- **Spec**: `SPEC-MOV` (`MOV-04`)
- **Level**: `integration`
- **Execution Mode**: `characterization`
- **Branch**: `atom-17-mov-04-enter-income-listed-in-month-query` (from `stage-04`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: MOV-04
Spec: SPEC-MOV
Nivel: integration
Modo: characterization
Dado: una unidad residencial, una cuenta y un tipo de ingreso validos
Cuando: se registra un ingreso para el mes (Y, M) y se consulta GET /api/v1/incomes/date-range/{Y}/{M}
Entonces: el listado del mes incluye el ingreso registrado con su importe en centimos
Cubre: src/Context/Income/Infrastructure/Http/Controller/GetIncomesByMonthController.php
Test: tests/Context/Income/Infrastructure/Http/Controller/GetIncomesByMonthControllerTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/Income/Infrastructure/Http/Controller/IncomeEnterPutController.php`
- `src/Context/Income/Infrastructure/Http/Controller/GetIncomesByMonthController.php`
- `tests/Context/Income/Infrastructure/Http/Controller/GetIncomesByMonthControllerTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/Income/Infrastructure/Http/Controller/GetIncomesByMonthControllerTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `MOV-04` en `tests/Context/Income/Infrastructure/Http/Controller/GetIncomesByMonthControllerTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Income/Infrastructure/Http/Controller/GetIncomesByMonthController.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-04 atom-17-mov-04-enter-income-listed-in-month-query
   ```
