# Work Card: `atom-27-ban-02-ofx-confirm-idempotency-key` (BAN-02)

- **Stage**: `stage-07` — SPEC-BAN y SPEC-LEC: Extracto OFX, Prevision y Libro
- **Spec**: `SPEC-BAN, SPEC-LEC` (`BAN-02`)
- **Level**: `integration`
- **Execution Mode**: `characterization`
- **Branch**: `atom-27-ban-02-ofx-confirm-idempotency-key` (from `stage-07`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: BAN-02
Spec: SPEC-BAN, SPEC-LEC
Nivel: integration
Modo: characterization
Dado: un extracto OFX con una clave de linea ya confirmada
Cuando: se reenvia la confirmacion para la misma linea en OfxConfirmPostController
Entonces: no duplica el movimiento ni la importacion en la base de datos
Cubre: src/Context/BankStatement/Infrastructure/Http/Controller/OfxConfirmPostController.php
Test: tests/Context/BankStatement/Infrastructure/Http/Controller/OfxConfirmPostControllerTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/BankStatement/Infrastructure/Http/Controller/OfxConfirmPostController.php`
- `tests/Context/BankStatement/Infrastructure/Http/Controller/OfxConfirmPostControllerTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/BankStatement/Infrastructure/Http/Controller/OfxConfirmPostControllerTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `BAN-02` en `tests/Context/BankStatement/Infrastructure/Http/Controller/OfxConfirmPostControllerTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/BankStatement/Infrastructure/Http/Controller/OfxConfirmPostController.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-07 atom-27-ban-02-ofx-confirm-idempotency-key
   ```
