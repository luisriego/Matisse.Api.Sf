# Work Card: `atom-20-glm-02-policy-snapshot-overrides-generation-command-params` (GLM-02)

- **Stage**: `stage-05` — SPEC-GLM: Gas y politica del mes
- **Spec**: `SPEC-GLM` (`GLM-02`)
- **Level**: `unit`
- **Execution Mode**: `characterization`
- **Branch**: `atom-20-glm-02-policy-snapshot-overrides-generation-command-params` (from `stage-05`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: GLM-02
Spec: SPEC-GLM
Nivel: unit
Modo: characterization
Dado: un mes con snapshot de politica mensual registrado y un comando de generacion con parametros distintos
Cuando: se ejecuta SlipGenerationCommandHandler
Entonces: la generacion aplica extraFeePerUnitCents y reserveFundPerUnitCents del snapshot de politica
Cubre: src/Context/Slip/Application/UseCase/SlipGeneration/SlipGenerationCommandHandler.php
Test: tests/Context/Slip/Application/UseCase/SlipGeneration/SlipGenerationCommandHandlerTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/Slip/Application/UseCase/SlipGeneration/SlipGenerationCommandHandler.php`
- `tests/Context/Slip/Application/UseCase/SlipGeneration/SlipGenerationCommandHandlerTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/Slip/Application/UseCase/SlipGeneration/SlipGenerationCommandHandlerTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `GLM-02` en `tests/Context/Slip/Application/UseCase/SlipGeneration/SlipGenerationCommandHandlerTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Slip/Application/UseCase/SlipGeneration/SlipGenerationCommandHandler.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-05 atom-20-glm-02-policy-snapshot-overrides-generation-command-params
   ```
