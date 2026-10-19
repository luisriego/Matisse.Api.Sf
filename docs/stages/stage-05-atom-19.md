# Work Card: `atom-19-glm-01-gas-price-formula-from-bill-kg-buffer` (GLM-01)

- **Stage**: `stage-05` — SPEC-GLM: Gas y politica del mes
- **Spec**: `SPEC-GLM` (`GLM-01`)
- **Level**: `unit`
- **Execution Mode**: `characterization`
- **Branch**: `atom-19-glm-01-gas-price-formula-from-bill-kg-buffer` (from `stage-05`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: GLM-01
Spec: SPEC-GLM
Nivel: unit
Modo: characterization
Dado: una factura de gas en centimos, capacidad de cilindro en kg y porcentaje de margen (buffer)
Cuando: se invoca Gas::definePrice(amount, capacity, buffer)
Entonces: calcula el precio por m3 segun la formula oficial redondeada al centimo y emite GasPriceWasDefined
Cubre: src/Context/Gas/Domain/Gas.php
Test: tests/Context/Gas/Domain/GasTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/Gas/Domain/Gas.php`
- `tests/Context/Gas/Domain/GasTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/Gas/Domain/GasTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `GLM-01` en `tests/Context/Gas/Domain/GasTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/Gas/Domain/Gas.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-05 atom-19-glm-01-gas-price-formula-from-bill-kg-buffer
   ```
