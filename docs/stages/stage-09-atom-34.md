# Tarjeta de Etapa: stage-09 — Átomo OPT-02 (`atom-34-opt-02-composite-indexes-for-period-queries`)

---

## 1. Peregrino & Mochila
- **Objetivo Atómico (SRP)**: OPT-02: Índices compuestos en PostgreSQL para consultas temporales en Slip, Expense e Income
- **Stage**: `stage-09` — SPEC-OPT: Optimización de Integridad, Mapeo Relacional y Resiliencia Backend
- **Spec**: `SPEC-OPT` (`OPT-02`) | **Nivel**: `integration` | **Modo**: `tdd`
- **Rama dedicada**: `atom-34-opt-02-composite-indexes-for-period-queries` (consolidación fast-forward en `stage-09`).
- **TDD Innegociable**: `RED -> GREEN -> REFACTOR -> GATE -> SELLO`.

---

## 2. Botas (Contexto & Especificación)
- **Artefacto principal bajo cobertura**: `src/Context/Expense/Infrastructure/Persistence/Doctrine/Expense.orm.xml`
- **Archivo de prueba objetivo**: `tests/Context/Slip/Infrastructure/Persistence/Doctrine/SlipQueryIndexIntegrationTest.php`
- **Contrato BDD Comprobable**:
  - **Dado (Given)**: consultas por rango de fechas y estado en tablas expense, income y slip
  - **Cuando (When)**: se declaran índices compuestos en los mapeos ORM XML y se aplica la migración
  - **Entonces (Then)**: el catálogo de PostgreSQL registra los índices B-Tree optimizados

---

## 3. Ropa (Modus Operandi)
1. **Test (RED / Characterization)**:
   - Ejecuta el oráculo de tests:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/Slip/Infrastructure/Persistence/Doctrine/SlipQueryIndexIntegrationTest.php
     ```
2. **Implementación (GREEN)**:
   - Declarar índices compuestos en los mapeos ORM XML y verificar con PHPUnit.
3. **Gate & Sello**:
   ```bash
   ./tools/cleanroom/gate.sh stage-09 atom-34-opt-02-composite-indexes-for-period-queries --auto-commit
   ```

---

## 4. Mapa con Fronteras (Scope Allowlist)
- `src/Context/Expense/Infrastructure/Persistence/Doctrine/Expense.orm.xml`
- `src/Context/Income/Infrastructure/Persistence/Doctrine/Income.orm.xml`
- `src/Context/Slip/Infrastructure/Persistence/Doctrine/Slip.orm.xml`
- `migrations/*`
- `tests/Context/Slip/Infrastructure/Persistence/Doctrine/SlipQueryIndexIntegrationTest.php`

---

## 5. Silbato & Libro de Frases
- Si el oráculo de `gate.sh` falla **3 veces consecutivas** -> `STATUS: BLOCKED`.
- Al completar con éxito el Gate, emite bloque YAML con `STATUS: SEALED`.
