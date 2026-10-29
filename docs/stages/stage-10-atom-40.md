# Tarjeta de Etapa: stage-10 — Átomo FIX-03 (`atom-40-fix-slip-anomaly-handler-and-notification-bugs`)

---

## 1. Peregrino & Mochila
- **Objetivo Atómico (SRP)**: FIX-03: Corrección de TypeError en CheckSlipTotalAnomalyCommandHandler, eliminación de email fantasma a test@example.com y uso de LoggerInterface en WelcomeResidentNotificationHandler
- **Stage**: `stage-10` — SPEC-FIX-INFRA: Corrección de Infraestructura HTTP, Eventos de Dominio y Runtime Bugs
- **Spec**: `SPEC-FIX-INFRA` (`FIX-03`) | **Nivel**: `unit` | **Modo**: `tdd`
- **Rama dedicada**: `atom-40-fix-slip-anomaly-handler-and-notification-bugs` (consolidación fast-forward en `stage-10`).
- **TDD Innegociable**: `RED -> GREEN -> REFACTOR -> GATE -> SELLO`.

---

## 2. Botas (Contexto & Especificación)
- **Artefacto principal bajo cobertura**: `src/Context/Slip/Application/UseCase/CheckSlipTotalAnomaly/CheckSlipTotalAnomalyCommandHandler.php`
- **Archivo de prueba objetivo**: `tests/Context/Slip/Application/UseCase/CheckSlipTotalAnomaly/CheckSlipTotalAnomalyCommandHandlerTest.php`
- **Contrato BDD Comprobable**:
  - **Dado (Given)**: CheckSlipTotalAnomalyCommandHandler devolviendo stdClass|null desde SlipAlertService, SendSlipNotificationHandler con test@example.com hardcoded y WelcomeResidentNotificationHandler con echo en stdout
  - **Cuando (When)**: se invoca CheckSlipTotalAnomalyCommandHandler, SendSlipNotificationHandler y WelcomeResidentNotificationHandler
  - **Entonces (Then)**: CheckSlipTotalAnomalyCommandHandler declara ?stdClass sin lanzar TypeError, SendSlipNotificationHandler resuelve destinatarios reales de ResidentUnitRepository (sin enviar a test@example.com) y WelcomeResidentNotificationHandler registra en LoggerInterface sin emitir salida por stdout
- **Casos de prueba obligatorios en `tests/Context/Slip/Application/UseCase/CheckSlipTotalAnomaly/CheckSlipTotalAnomalyCommandHandlerTest.php`**:
  1. `testReturnsStdClassAlertWhenAnomalyIsDetectedWithoutTypeError`: Mockea `SlipAlertService` devolviendo un `stdClass` con `{titulo, diagnostico, recomendacao}` y verifica que `CheckSlipTotalAnomalyCommandHandler::__invoke` devuelva ese `stdClass` sin `TypeError`.
  2. `testReturnsNullWhenAmountIsWithinNormalRange`: Verifica que devuelva `null` cuando `SlipAlertService` retorna `null`.
  3. `testWelcomeResidentNotificationHandlerDoesNotEchoToStdout`: Ejecuta `WelcomeResidentNotificationHandler::__invoke` capturando `ob_start()`/`ob_get_clean()` y verifica que la salida estándar sea exactamente `''` (vacía).

---

## 3. Ropa (Modus Operandi)
1. **Test (RED / Characterization)**:
   - Crea o actualiza `tests/Context/Slip/Application/UseCase/CheckSlipTotalAnomaly/CheckSlipTotalAnomalyCommandHandlerTest.php` con los casos de prueba obligatorios de la sección 2.
   - Ejecuta el oráculo de tests:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/Slip/Application/UseCase/CheckSlipTotalAnomaly/CheckSlipTotalAnomalyCommandHandlerTest.php
     ```
2. **Implementación (GREEN)**:
   - En `src/Context/Slip/Application/UseCase/CheckSlipTotalAnomaly/CheckSlipTotalAnomalyCommandHandler.php`: importar `stdClass` y cambiar el tipo de retorno de `__invoke(CheckSlipTotalAnomalyCommand $command): ?string` a `?stdClass`.
   - En `src/Context/ResidentUnit/Application/Message/WelcomeResidentNotificationHandler.php`: inyectar `?LoggerInterface $logger = null`, eliminar el `echo sprintf(...)` y reemplazarlo por `$this->logger?->info(...)`.
   - En `src/Context/Slip/Application/Message/SendSlipNotificationHandler.php`: inyectar `?ResidentUnitRepository $residentUnitRepository = null`; si no hay destinatarios reales configurados en la unidad, retornar sin enviar a `'test@example.com'`.
   - Vuelve a ejecutar PHPUnit y verifica verde:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/Slip/Application/UseCase/CheckSlipTotalAnomaly/CheckSlipTotalAnomalyCommandHandlerTest.php
     ```
   - Ejecuta análisis estático y formateador sobre los archivos tocados:
     ```bash
     ./tools/cleanroom/exec_php.sh tools/php-cs-fixer/vendor/bin/php-cs-fixer fix src/Context/Slip/Application/UseCase/CheckSlipTotalAnomaly/CheckSlipTotalAnomalyCommandHandler.php src/Context/Slip/Application/Message/SendSlipNotificationHandler.php src/Context/ResidentUnit/Application/Message/WelcomeResidentNotificationHandler.php tests/Context/Slip/Application/UseCase/CheckSlipTotalAnomaly/CheckSlipTotalAnomalyCommandHandlerTest.php
     ./tools/cleanroom/exec_php.sh vendor/bin/phpstan analyse src/Context/Slip/Application/UseCase/CheckSlipTotalAnomaly/CheckSlipTotalAnomalyCommandHandler.php src/Context/Slip/Application/Message/SendSlipNotificationHandler.php src/Context/ResidentUnit/Application/Message/WelcomeResidentNotificationHandler.php tests/Context/Slip/Application/UseCase/CheckSlipTotalAnomaly/CheckSlipTotalAnomalyCommandHandlerTest.php --memory-limit=512M
     ```
3. **Gate & Sello**:
   ```bash
   ./tools/cleanroom/gate.sh stage-10 atom-40-fix-slip-anomaly-handler-and-notification-bugs --auto-commit
   ```

---

## 4. Mapa con Fronteras (Scope Allowlist)
Solo se permite crear o modificar los siguientes archivos durante este átomo (cualquier archivo fuera de esta lista abortará el `Gate 0`):
- `src/Context/Slip/Application/UseCase/CheckSlipTotalAnomaly/CheckSlipTotalAnomalyCommandHandler.php`
- `src/Context/Slip/Application/Message/SendSlipNotificationHandler.php`
- `src/Context/ResidentUnit/Application/Message/WelcomeResidentNotificationHandler.php`
- `tests/Context/Slip/Application/UseCase/CheckSlipTotalAnomaly/CheckSlipTotalAnomalyCommandHandlerTest.php`

---

## 5. Silbato & Libro de Frases
- Si el oráculo de `gate.sh` falla **3 veces consecutivas** o supera **10 minutos** de bucle en la misma hipótesis -> el circuit breaker registrará `STATUS: BLOCKED` en `docs/stages/log.md`.
- Al completar con éxito el Gate (código de salida `0`), emite bloque YAML final con `STATUS: SEALED`:
```yaml
ATOM: atom-40-fix-slip-anomaly-handler-and-notification-bugs
STAGE: stage-10
SPEC: FIX-03
STATUS: SEALED
```
