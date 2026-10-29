# Tarjeta de Etapa: stage-11 — Átomo FIX-05 (`atom-42-unify-slip-factory-gas-with-explain`)

---

## 1. Peregrino & Mochila
- **Objetivo Atómico (SRP)**: FIX-05: Unificación del cálculo de gas en SlipFactory y SlipGenerationCommandHandler con SlipGenerationBreakdownBuilder (explain === generate)
- **Stage**: `stage-11` — SPEC-FIX-FIN: Integridad Financiera en Generación de Cuotas, Política Mensual y Saldo de Cuentas
- **Spec**: `SPEC-FIX-FIN` (`FIX-05`) | **Nivel**: `unit` | **Modo**: `tdd`
- **Rama dedicada**: `atom-42-unify-slip-factory-gas-with-explain` (consolidación fast-forward en `stage-11`).
- **TDD Innegociable**: `RED -> GREEN -> REFACTOR -> GATE -> SELLO`.

---

## 2. Botas (Contexto & Especificación)
- **Artefacto principal bajo cobertura**: `src/Context/Slip/Domain/Service/SlipFactory.php`
- **Archivo de prueba objetivo**: `tests/Context/Slip/Domain/Service/SlipFactoryGasParityTest.php`
- **Contrato BDD Comprobable**:
  - **Dado (Given)**: lecturas de gas por m3 registradas en EventStore (GasConsumptionBreakdownResolver) o gastos individuales GAS en el mes anterior (GasExpenseByUnitResolver), y un precio de gas de política mensual
  - **Cuando (When)**: se generan los recibos con SlipFactory::createFromExpensesAndUnits y se compara con SlipGenerationBreakdownBuilder::build
  - **Entonces (Then)**: SlipFactory usa GasConsumptionBreakdownResolver (con fallback a GasExpenseByUnitResolver si el total por lecturas es 0) y acepta gasPricePerM3CentsOverride produciendo exactamente los mismos importes por unidad que explain
- **Casos de prueba obligatorios en `tests/Context/Slip/Domain/Service/SlipFactoryGasParityTest.php`**:
  1. `testSlipFactoryUsesGasConsumptionBreakdownWhenReadingsExist`: Configura lecturas de gas en `GasConsumptionBreakdownResolver` (ej. 10 m3 * 2500 cents = 25000 cents) sin gastos individuales GAS y verifica que `SlipFactory` incluya los 25000 cents en el `Slip` generado igual que `SlipGenerationBreakdownBuilder`.
  2. `testSlipFactoryFallsBackToGasExpenseByUnitResolverWhenReadingsTotalZero`: Cuando `GasConsumptionBreakdownResolver` devuelve `gasTotalCents=0` pero `GasExpenseByUnitResolver` tiene importes para la unidad, verifica que `SlipFactory` mantenga esos importes como fallback.

---

## 3. Ropa (Modus Operandi)
1. **Test (RED / Characterization)**:
   - Crea o actualiza `tests/Context/Slip/Domain/Service/SlipFactoryGasParityTest.php` con los casos de prueba obligatorios de la sección 2.
   - Ejecuta el oráculo de tests:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/Slip/Domain/Service/SlipFactoryGasParityTest.php
     ```
2. **Implementación (GREEN)**:
   - En `src/Context/Slip/Domain/Service/SlipFactory.php`: añadir `?GasConsumptionBreakdownResolver $gasConsumptionBreakdownResolver = null` al constructor y `?int $gasPricePerM3CentsOverride = null` a `createFromExpensesAndUnits(...)`. Si `$this->gasConsumptionBreakdownResolver !== null`, calcular `$gasBreakdown = $this->gasConsumptionBreakdownResolver->breakdownForMonth(...)` y si `$gasBreakdown['gasTotalCents'] > 0` usar sus `gasCents` por unidad; en caso contrario usar `$this->gasExpenseByUnitResolver->sumByResidentUnitForCalendarMonth(...)`.
   - En `src/Context/Slip/Application/UseCase/SlipGeneration/SlipGenerationCommandHandler.php`: resolver `$gasPricePerM3CentsOverride` desde `$policy->gasPricePerM3Cents()` cuando `$policy->hasPolicy()` es `true` y pasarlo a `$this->slipFactory->createFromExpensesAndUnits(...)`.
   - Vuelve a ejecutar PHPUnit y verifica verde:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/Slip/Domain/Service/SlipFactoryGasParityTest.php
     ```
   - Ejecuta análisis estático y formateador sobre los archivos tocados:
     ```bash
     ./tools/cleanroom/exec_php.sh tools/php-cs-fixer/vendor/bin/php-cs-fixer fix src/Context/Slip/Domain/Service/SlipFactory.php src/Context/Slip/Application/UseCase/SlipGeneration/SlipGenerationCommandHandler.php tests/Context/Slip/Domain/Service/SlipFactoryGasParityTest.php
     ./tools/cleanroom/exec_php.sh vendor/bin/phpstan analyse src/Context/Slip/Domain/Service/SlipFactory.php src/Context/Slip/Application/UseCase/SlipGeneration/SlipGenerationCommandHandler.php tests/Context/Slip/Domain/Service/SlipFactoryGasParityTest.php --memory-limit=512M
     ```
3. **Gate & Sello**:
   ```bash
   ./tools/cleanroom/gate.sh stage-11 atom-42-unify-slip-factory-gas-with-explain --auto-commit
   ```

---

## 4. Mapa con Fronteras (Scope Allowlist)
Solo se permite crear o modificar los siguientes archivos durante este átomo (cualquier archivo fuera de esta lista abortará el `Gate 0`):
- `src/Context/Slip/Domain/Service/SlipFactory.php`
- `src/Context/Slip/Application/UseCase/SlipGeneration/SlipGenerationCommandHandler.php`
- `tests/Context/Slip/Domain/Service/SlipFactoryGasParityTest.php`
- `tests/Context/Slip/Application/UseCase/SlipGeneration/SlipGenerationCommandHandlerTest.php`

---

## 5. Silbato & Libro de Frases
- Si el oráculo de `gate.sh` falla **3 veces consecutivas** o supera **10 minutos** de bucle en la misma hipótesis -> el circuit breaker registrará `STATUS: BLOCKED` en `docs/stages/log.md`.
- Al completar con éxito el Gate (código de salida `0`), emite bloque YAML final con `STATUS: SEALED`:
```yaml
ATOM: atom-42-unify-slip-factory-gas-with-explain
STAGE: stage-11
SPEC: FIX-05
STATUS: SEALED
```
