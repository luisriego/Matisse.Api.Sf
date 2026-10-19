# Work Card: `atom-29-lec-02-forecast-returns-target-month` (LEC-02)

- **Stage**: `stage-07` — SPEC-BAN y SPEC-LEC: Extracto OFX, Prevision y Libro
- **Spec**: `SPEC-BAN, SPEC-LEC` (`LEC-02`)
- **Level**: `integration`
- **Execution Mode**: `characterization`
- **Branch**: `atom-29-lec-02-forecast-returns-target-month` (from `stage-07`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: LEC-02
Spec: SPEC-BAN, SPEC-LEC
Nivel: integration
Modo: characterization
Dado: unidades residenciales activas en el condominio
Cuando: se consulta GET /api/v1/forecast?targetMonth=2026-01
Entonces: responde 200 OK con targetMonth=2026-01 y el desglose tecnico
Cubre: src/Context/Forecast/Infrastructure/Http/Controller/ForecastGetController.php
Test: tests/Context/Forecast/Infrastructure/Http/Controller/ForecastGetControllerTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/Forecast/Infrastructure/Http/Controller/ForecastGetController.php`
- `tests/Context/Forecast/Infrastructure/Http/Controller/ForecastGetControllerTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/Forecast/Infrastructure/Http/Controller/ForecastGetControllerTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `LEC-02` en `tests/Context/Forecast/Infrastructure/Http/Controller/ForecastGetControllerTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Forecast/Infrastructure/Http/Controller/ForecastGetController.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-07 atom-29-lec-02-forecast-returns-target-month
   ```
