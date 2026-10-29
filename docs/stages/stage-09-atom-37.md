# Tarjeta de Etapa: stage-09 — Átomo OPT-05 (`atom-37-opt-05-health-check-and-setup-required-endpoint`)

---

## 1. Peregrino & Mochila
- **Objetivo Atómico (SRP)**: OPT-05: Endpoint de Health Check y estado Setup Required para la plataforma
- **Stage**: `stage-09` — SPEC-OPT: Optimización de Integridad, Mapeo Relacional y Resiliencia Backend
- **Spec**: `SPEC-OPT` (`OPT-05`) | **Nivel**: `integration` | **Modo**: `tdd`
- **Rama dedicada**: `atom-37-opt-05-health-check-and-setup-required-endpoint` (consolidación fast-forward en `stage-09`).
- **TDD Innegociable**: `RED -> GREEN -> REFACTOR -> GATE -> SELLO`.

---

## 2. Botas (Contexto & Especificación)
- **Artefacto principal bajo cobertura**: `src/Shared/Infrastructure/Symfony/Controller/HealthCheckController.php`
- **Archivo de prueba objetivo**: `tests/Shared/Infrastructure/Symfony/Controller/HealthCheckControllerTest.php`
- **Contrato BDD Comprobable**:
  - **Dado (Given)**: la plataforma Matisse Api con múltiples dependencias (Doctrine, Redis, etc.)
  - **Cuando (When)**: se implementa un endpoint GET /api/health que verifica el estado de cada dependencia
  - **Entonces (Then)**: responde 200 OK con detalle de cada servicio o 503 Service Unavailable si alguna dependencia falla

---

## 3. Ropa (Modus Operandi)
1. **Test (RED / Characterization)**:
   - Ejecuta el oráculo de tests:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Shared/Infrastructure/Symfony/Controller/HealthCheckControllerTest.php
     ```
2. **Implementación (GREEN)**:
   - Implementar `HealthCheckController` y verificar verde en PHPUnit.
3. **Gate & Sello**:
   ```bash
   ./tools/cleanroom/gate.sh stage-09 atom-37-opt-05-health-check-and-setup-required-endpoint --auto-commit
   ```

---

## 4. Mapa con Fronteras (Scope Allowlist)
- `src/Shared/Infrastructure/Symfony/Controller/HealthCheckController.php`
- `tests/Shared/Infrastructure/Symfony/Controller/HealthCheckControllerTest.php`
- `config/routes.yaml`
- `src/Shared/Infrastructure/Symfony/routes.yaml`
- `config/services.yaml`
- `config/packages/security.yaml`

---

## 5. Silbato & Libro de Frases
- Si el oráculo de `gate.sh` falla **3 veces consecutivas** -> `STATUS: BLOCKED`.
- Al completar con éxito el Gate, emite bloque YAML con `STATUS: SEALED`.
