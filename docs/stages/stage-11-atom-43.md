# Tarjeta de Etapa: stage-11 — Átomo FIX-06 (`atom-43-preserve-setup-opening-month-in-billing-policy`)

---

## 1. Peregrino & Mochila
- **Objetivo Atómico (SRP)**: FIX-06: Preservación del snapshot de apertura del Setup (OpeningReferenceMonthWasRecorded) al existir políticas mensuales posteriores
- **Stage**: `stage-11` — SPEC-FIX-FIN: Integridad Financiera en Generación de Cuotas, Política Mensual y Saldo de Cuentas
- **Spec**: `SPEC-FIX-FIN` (`FIX-06`) | **Nivel**: `unit` | **Modo**: `tdd`
- **Rama dedicada**: `atom-43-preserve-setup-opening-month-in-billing-policy` (consolidación fast-forward en `stage-11`).
- **TDD Innegociable**: `RED -> GREEN -> REFACTOR -> GATE -> SELLO`.

---

## 2. Botas (Contexto & Especificación)
- **Artefacto principal bajo cobertura**: `src/Context/BillingPolicy/Application/Service/BillingPolicyResolverService.php`
- **Archivo de prueba objetivo**: `tests/Context/BillingPolicy/Application/Service/BillingPolicyResolverServiceTest.php`
- **Contrato BDD Comprobable**:
  - **Dado (Given)**: un evento OpeningReferenceMonthWasRecorded para 2026-01 en EventStore y un BillingPolicyMonthSnapshot explícito para 2026-06 en la tabla de políticas
  - **Cuando (When)**: se invoca BillingPolicyResolverService::resolve('2026-03') para un mes intermedio entre el setup (2026-01) y la política explícita (2026-06)
  - **Entonces (Then)**: hereda los valores del mes de apertura 2026-01 (hasPolicy=true, isInherited=true, sourceMonth='2026-01') sin perder el baseline de apertura
- **Casos de prueba obligatorios en `tests/Context/BillingPolicy/Application/Service/BillingPolicyResolverServiceTest.php`**:
  1. `testResolveForMonthBetweenOpeningReferenceAndLaterExplicitSnapshotInheritsOpeningReference`: Registra `OpeningReferenceMonthWasRecorded` en `2026-01` y un `BillingPolicyMonthSnapshot` en `2026-06`; al resolver `2026-03` verifica `hasPolicy() === true`, `isInherited() === true` y `sourceMonth() === '2026-01'`.
  2. `testExplicitSnapshotOnSameMonthOverridesOpeningReference`: Si existe `BillingPolicyMonthSnapshot` explícito para el mismo mes `2026-01` del setup, verifica que prevalezca el snapshot explícito (`source() === 'explicit'`).

---

## 3. Ropa (Modus Operandi)
1. **Test (RED / Characterization)**:
   - Crea o actualiza `tests/Context/BillingPolicy/Application/Service/BillingPolicyResolverServiceTest.php` con los casos de prueba obligatorios de la sección 2.
   - Ejecuta el oráculo de tests:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/BillingPolicy/Application/Service/BillingPolicyResolverServiceTest.php
     ```
2. **Implementación (GREEN)**:
   - En `src/Context/BillingPolicy/Application/Service/BillingPolicyResolverService.php`: en `buildSnapshotIndex()`, eliminar el `if ($snapshots !== []) { return $snapshots; }` prematuro. En su lugar, consultar siempre `latestOpeningReference()` y si existe un `$referenceMonth` válido y `!isset($snapshots[$referenceMonth])`, insertar `$snapshots[$referenceMonth] = BillingPolicySnapshot::fromOpeningReference(...)`.
   - Vuelve a ejecutar PHPUnit y verifica verde:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/BillingPolicy/Application/Service/BillingPolicyResolverServiceTest.php
     ```
   - Ejecuta análisis estático y formateador sobre los archivos tocados:
     ```bash
     ./tools/cleanroom/exec_php.sh tools/php-cs-fixer/vendor/bin/php-cs-fixer fix src/Context/BillingPolicy/Application/Service/BillingPolicyResolverService.php tests/Context/BillingPolicy/Application/Service/BillingPolicyResolverServiceTest.php
     ./tools/cleanroom/exec_php.sh vendor/bin/phpstan analyse src/Context/BillingPolicy/Application/Service/BillingPolicyResolverService.php tests/Context/BillingPolicy/Application/Service/BillingPolicyResolverServiceTest.php --memory-limit=512M
     ```
3. **Gate & Sello**:
   ```bash
   ./tools/cleanroom/gate.sh stage-11 atom-43-preserve-setup-opening-month-in-billing-policy --auto-commit
   ```

---

## 4. Mapa con Fronteras (Scope Allowlist)
Solo se permite crear o modificar los siguientes archivos durante este átomo (cualquier archivo fuera de esta lista abortará el `Gate 0`):
- `src/Context/BillingPolicy/Application/Service/BillingPolicyResolverService.php`
- `tests/Context/BillingPolicy/Application/Service/BillingPolicyResolverServiceTest.php`

---

## 5. Silbato & Libro de Frases
- Si el oráculo de `gate.sh` falla **3 veces consecutivas** o supera **10 minutos** de bucle en la misma hipótesis -> el circuit breaker registrará `STATUS: BLOCKED` en `docs/stages/log.md`.
- Al completar con éxito el Gate (código de salida `0`), emite bloque YAML final con `STATUS: SEALED`:
```yaml
ATOM: atom-43-preserve-setup-opening-month-in-billing-policy
STAGE: stage-11
SPEC: FIX-06
STATUS: SEALED
```
