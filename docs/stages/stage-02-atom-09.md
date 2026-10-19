# Work Card: `atom-09-ten-02-set-syndic-demotes-previous` (TEN-02)

- **Stage**: `stage-02` — SPEC-TEN: Un condominio y un sindico
- **Spec**: `SPEC-TEN` (`TEN-02`)
- **Level**: `integration`
- **Execution Mode**: `tdd`
- **Branch**: `atom-09-ten-02-set-syndic-demotes-previous` (from `stage-02`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: TEN-02
Spec: SPEC-TEN
Nivel: integration
Modo: tdd
Dado: dos usuarios persistidos donde el usuario A tiene ROLE_SYNDIC y el usuario B es residente
Cuando: se ejecuta el comando app:syndic:set con el email del usuario B
Entonces: el usuario B adquiere ROLE_SYNDIC, el usuario A es degradado a residente y solo queda un sindico en base de datos
Cubre: src/Context/User/Infrastructure/Console/SetSyndicCommand.php
Test: tests/Context/User/Infrastructure/Console/SetSyndicCommandTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/User/Infrastructure/Console/SetSyndicCommand.php`
- `tests/Context/User/Infrastructure/Console/SetSyndicCommandTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/User/Infrastructure/Console/SetSyndicCommandTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `TEN-02` en `tests/Context/User/Infrastructure/Console/SetSyndicCommandTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/User/Infrastructure/Console/SetSyndicCommand.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-02 atom-09-ten-02-set-syndic-demotes-previous
   ```
