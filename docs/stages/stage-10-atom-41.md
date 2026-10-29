# Tarjeta de Etapa: stage-10 — Átomo FIX-04 (`atom-41-fix-income-routes-and-slip-workflow-service`)

---

## 1. Peregrino & Mochila
- **Objetivo Atómico (SRP)**: FIX-04: Saneamiento de routes.yaml en Income y corrección de SlipWorkflowService y SlipStateSync
- **Stage**: `stage-10` — SPEC-FIX-INFRA: Corrección de Infraestructura HTTP, Eventos de Dominio y Runtime Bugs
- **Spec**: `SPEC-FIX-INFRA` (`FIX-04`) | **Nivel**: `unit` | **Modo**: `tdd`
- **Rama dedicada**: `atom-41-fix-income-routes-and-slip-workflow-service` (consolidación fast-forward en `stage-10`).
- **TDD Innegociable**: `RED -> GREEN -> REFACTOR -> GATE -> SELLO`.

---

## 2. Botas (Contexto & Especificación)
- **Artefacto principal bajo cobertura**: `src/Context/Slip/Application/Service/SlipWorkflowService.php`
- **Archivo de prueba objetivo**: `tests/Context/Slip/Application/Service/SlipWorkflowServiceTest.php`
- **Contrato BDD Comprobable**:
  - **Dado (Given)**: routes.yaml de Income con la clave corrupta 'para que income_health_check:' y controladores inexistentes, SlipWorkflowService con #[Autowire(service: 'slip.state_machine')] y SlipStateSync invocando métodos inexistentes en Slip
  - **Cuando (When)**: se corrige routes.yaml en Income, se actualiza SlipWorkflowService a 'state_machine.slip' y se alinea SlipStateSync con Slip::getStatus()
  - **Entonces (Then)**: las rutas de Income usan nombres válidos y controladores existentes, SlipWorkflowService referencia state_machine.slip y SlipStateSync sincroniza o valida el estado sin errores
- **Casos de prueba obligatorios en `tests/Context/Slip/Application/Service/SlipWorkflowServiceTest.php`**:
  1. `testSendAppliesSendTransitionWhenAllowed`: Verifica que `SlipWorkflowService::send($slip)` verifique `can($slip, 'send')` y ejecute `apply($slip, 'send')`.
  2. `testCancelAppliesFirstAvailableVoidTransition`: Verifica que `SlipWorkflowService::cancel($slip)` aplique `void_from_submitted` cuando está habilitada.
  3. `testCancelThrowsLogicExceptionWhenNoVoidTransitionIsAllowed`: Verifica que lance `LogicException` cuando ninguna transición `void_*` está permitida.
  4. `testIncomeRoutesYamlHasValidRouteNamesAndExistingControllers`: Parsea `src/Context/Income/Infrastructure/Http/routes.yaml` con `Yaml::parseFile` y verifica que no exista la clave `'para que income_health_check'` y que todos los controladores activos existan con `class_exists()`.

---

## 3. Ropa (Modus Operandi)
1. **Test (RED / Characterization)**:
   - Crea o actualiza `tests/Context/Slip/Application/Service/SlipWorkflowServiceTest.php` con los casos de prueba obligatorios de la sección 2.
   - Ejecuta el oráculo de tests:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/Slip/Application/Service/SlipWorkflowServiceTest.php
     ```
2. **Implementación (GREEN)**:
   - En `src/Context/Income/Infrastructure/Http/routes.yaml`: corregir `'para que income_health_check:'` a `'income_health_check:'` y comentar/desactivar las rutas cuyos controladores aún no existen (`GetIncomeTypesController`, `GetInactiveIncomesByDateRangeController`) igual que las demás rutas comentadas.
   - En `src/Context/Slip/Application/Service/SlipWorkflowService.php`: cambiar `#[Autowire(service: 'slip.state_machine')]` a `#[Autowire(service: 'state_machine.slip')]` y soportar también `'pay_from_overdue'` en `pay(object $slip)`.
   - En `src/Context/Slip/Application/SlipStateSync.php`: soportar objetos `Slip` usando `method_exists($slip, 'getCurrentPlace')` y `method_exists($slip, 'setStatus')` de forma segura.
   - Vuelve a ejecutar PHPUnit y verifica verde:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/Slip/Application/Service/SlipWorkflowServiceTest.php
     ```
   - Ejecuta análisis estático y formateador sobre los archivos tocados:
     ```bash
     ./tools/cleanroom/exec_php.sh tools/php-cs-fixer/vendor/bin/php-cs-fixer fix src/Context/Slip/Application/Service/SlipWorkflowService.php src/Context/Slip/Application/SlipStateSync.php tests/Context/Slip/Application/Service/SlipWorkflowServiceTest.php
     ./tools/cleanroom/exec_php.sh vendor/bin/phpstan analyse src/Context/Slip/Application/Service/SlipWorkflowService.php src/Context/Slip/Application/SlipStateSync.php tests/Context/Slip/Application/Service/SlipWorkflowServiceTest.php --memory-limit=512M
     ```
3. **Gate & Sello**:
   ```bash
   ./tools/cleanroom/gate.sh stage-10 atom-41-fix-income-routes-and-slip-workflow-service --auto-commit
   ```

---

## 4. Mapa con Fronteras (Scope Allowlist)
Solo se permite crear o modificar los siguientes archivos durante este átomo (cualquier archivo fuera de esta lista abortará el `Gate 0`):
- `src/Context/Income/Infrastructure/Http/routes.yaml`
- `src/Context/Slip/Application/Service/SlipWorkflowService.php`
- `src/Context/Slip/Application/SlipStateSync.php`
- `tests/Context/Slip/Application/Service/SlipWorkflowServiceTest.php`

---

## 5. Silbato & Libro de Frases
- Si el oráculo de `gate.sh` falla **3 veces consecutivas** o supera **10 minutos** de bucle en la misma hipótesis -> el circuit breaker registrará `STATUS: BLOCKED` en `docs/stages/log.md`.
- Al completar con éxito el Gate (código de salida `0`), emite bloque YAML final con `STATUS: SEALED`:
```yaml
ATOM: atom-41-fix-income-routes-and-slip-workflow-service
STAGE: stage-10
SPEC: FIX-04
STATUS: SEALED
```
