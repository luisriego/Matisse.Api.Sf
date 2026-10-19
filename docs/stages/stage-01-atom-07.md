# Work Card: `atom-07-inf-07-explain-matches-generated-slips-sum` (INF-07)

- **Stage**: `stage-01` — SPEC-INF: Informar el valor de cada unidad (Desglose, Picos y Ventana de Generacion)
- **Spec**: `SPEC-INF` (`INF-07`)
- **Level**: `acceptance`
- **Execution Mode**: `tdd`
- **Branch**: `atom-07-inf-07-explain-matches-generated-slips-sum` (from `stage-01`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: INF-07
Spec: SPEC-INF
Nivel: acceptance
Modo: tdd
Dado: un condominio con unidades activas, gastos del mes N (EQUAL, FRACTION, INDIVIDUAL) y parametros de generacion
Cuando: se consulta GET /api/v1/slips/generation/explain y se ejecuta POST /api/v1/slips/generate para el mismo mes y parametros
Entonces: los importes por unidad y la suma total de explain coinciden exactamente con las cuotas persistidas devueltas por GET /api/v1/slips/{dueYear}/{dueMonth}
Cubre: src/Context/Slip/Infrastructure/Http/Controller/ExplainSlipGenerationGetController.php
Test: tests/Acceptance/Slip/ExplainMatchesGeneratedSlipsAcceptanceTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `phpunit.xml.dist`
- `tests/Acceptance/Slip/ExplainMatchesGeneratedSlipsAcceptanceTest.php`
- `src/Context/Slip/Infrastructure/Http/Controller/ExplainSlipGenerationGetController.php`
- `src/Context/Slip/Infrastructure/Http/Controller/SlipGenerationPostController.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Acceptance/Slip/ExplainMatchesGeneratedSlipsAcceptanceTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `INF-07` en `tests/Acceptance/Slip/ExplainMatchesGeneratedSlipsAcceptanceTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Slip/Infrastructure/Http/Controller/ExplainSlipGenerationGetController.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-01 atom-07-inf-07-explain-matches-generated-slips-sum
   ```
