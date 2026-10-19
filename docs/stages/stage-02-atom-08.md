# Work Card: `atom-08-ten-01-promote-syndic-single-role` (TEN-01)

- **Stage**: `stage-02` — SPEC-TEN: Un condominio y un sindico
- **Spec**: `SPEC-TEN` (`TEN-01`)
- **Level**: `unit`
- **Execution Mode**: `characterization`
- **Branch**: `atom-08-ten-01-promote-syndic-single-role` (from `stage-02`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: TEN-01
Spec: SPEC-TEN
Nivel: unit
Modo: characterization
Dado: un agregado User con rol residente
Cuando: se invoca promoteToSyndic repetidas veces y posteriormente demoteToResident
Entonces: promoteToSyndic garantiza un unico ROLE_SYNDIC en getRoles y demoteToResident lo elimina dejando ROLE_USER
Cubre: src/Context/User/Domain/User.php
Test: tests/Context/User/Domain/UserTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/User/Domain/User.php`
- `tests/Context/User/Domain/UserTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/User/Domain/UserTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `TEN-01` en `tests/Context/User/Domain/UserTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/User/Domain/User.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-02 atom-08-ten-01-promote-syndic-single-role
   ```
