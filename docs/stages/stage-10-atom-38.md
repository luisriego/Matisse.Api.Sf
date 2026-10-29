# Tarjeta de Etapa: stage-10 — Átomo FIX-01 (`atom-38-fix-api-controller-exception-listener`)

---

## 1. Peregrino & Mochila
- **Objetivo Atómico (SRP)**: FIX-01: Soporte de controladores invocables en ApiControllerExceptionListener, prioridad 150 y mapeo 422 InvalidDataException
- **Stage**: `stage-10` — SPEC-FIX-INFRA: Corrección de Infraestructura HTTP, Eventos de Dominio y Runtime Bugs
- **Spec**: `SPEC-FIX-INFRA` (`FIX-01`) | **Nivel**: `unit` | **Modo**: `tdd`
- **Rama dedicada**: `atom-38-fix-api-controller-exception-listener` (consolidación fast-forward en `stage-10`).
- **TDD Innegociable**: `RED -> GREEN -> REFACTOR -> GATE -> SELLO`.

---

## 2. Botas (Contexto & Especificación)
- **Artefacto principal bajo cobertura**: `src/Shared/Infrastructure/Symfony/ApiControllerExceptionListener.php`
- **Archivo de prueba objetivo**: `tests/Shared/Infrastructure/Symfony/ApiControllerExceptionListenerTest.php`
- **Contrato BDD Comprobable**:
  - **Dado (Given)**: un controlador invocable que extiende ApiController y define exceptions(), o una InvalidDataException lanzada en el kernel
  - **Cuando (When)**: ApiControllerExceptionListener procesa ExceptionEvent con prioridad 150 (antes de JsonTransformerExceptionListener a 100)
  - **Entonces (Then)**: extrae la instancia de ApiController tanto si getController() es objeto invocable como array [controller, method] y devuelve el HTTP status mapeado (y JsonTransformerExceptionListener mapea InvalidDataException a 422)
- **Casos de prueba obligatorios en `tests/Shared/Infrastructure/Symfony/ApiControllerExceptionListenerTest.php`**:
  1. `testInvokableApiControllerMapsCustomExceptionToConfiguredStatusCode`: Crea un controlador anónimo que extiende ApiController (__invoke) mapeando RuntimeException => 409; dispara ApiControllerExceptionListener::onKernelException y verifica JsonResponse con status 409 y claves class, code, message.
  2. `testArrayTupleApiControllerMapsCustomExceptionToConfiguredStatusCode`: Pasa [$controller, '__invoke'] como controlador en ExceptionEvent y verifica JsonResponse con status 409.
  3. `testSubscribedEventsRegistersPriority150AboveJsonTransformer`: Verifica que ApiControllerExceptionListener::getSubscribedEvents()[KernelEvents::EXCEPTION] sea ['onKernelException', 150].
  4. `testJsonTransformerExceptionListenerMapsInvalidDataExceptionTo422`: Dispara JsonTransformerExceptionListener::onKernelException con App\Shared\Domain\Exception\InvalidDataException y verifica status 422 (Response::HTTP_UNPROCESSABLE_ENTITY).

---

## 3. Ropa (Modus Operandi)
1. **Test (RED / Characterization)**:
   - Crea o actualiza `tests/Shared/Infrastructure/Symfony/ApiControllerExceptionListenerTest.php` con los casos de prueba obligatorios de la sección 2.
   - Ejecuta el oráculo de tests:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Shared/Infrastructure/Symfony/ApiControllerExceptionListenerTest.php
     ```
2. **Implementación (GREEN)**:
   - En `src/Shared/Infrastructure/Symfony/ApiControllerExceptionListener.php`: cambiar prioridad en `getSubscribedEvents()` de `0` a `150` (`KernelEvents::EXCEPTION => ['onKernelException', 150]`).
   - En `ApiControllerExceptionListener::onKernelException`: resolver `$apiController = $controller instanceof ApiController ? $controller : (is_array($controller) && ($controller[0] ?? null) instanceof ApiController ? $controller[0] : null);` si es `null`, `return`.
   - En `src/Shared/Infrastructure/JsonTransformerExceptionListener.php`: importar `App\Shared\Domain\Exception\InvalidDataException` y si `$e instanceof InvalidDataException` asignar `$data['code'] = Response::HTTP_UNPROCESSABLE_ENTITY` (422).
   - Vuelve a ejecutar PHPUnit y verifica verde:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Shared/Infrastructure/Symfony/ApiControllerExceptionListenerTest.php
     ```
   - Ejecuta análisis estático y formateador sobre los archivos tocados:
     ```bash
     ./tools/cleanroom/exec_php.sh tools/php-cs-fixer/vendor/bin/php-cs-fixer fix src/Shared/Infrastructure/Symfony/ApiControllerExceptionListener.php src/Shared/Infrastructure/JsonTransformerExceptionListener.php tests/Shared/Infrastructure/Symfony/ApiControllerExceptionListenerTest.php
     ./tools/cleanroom/exec_php.sh vendor/bin/phpstan analyse src/Shared/Infrastructure/Symfony/ApiControllerExceptionListener.php src/Shared/Infrastructure/JsonTransformerExceptionListener.php tests/Shared/Infrastructure/Symfony/ApiControllerExceptionListenerTest.php --memory-limit=512M
     ```
3. **Gate & Sello**:
   ```bash
   ./tools/cleanroom/gate.sh stage-10 atom-38-fix-api-controller-exception-listener --auto-commit
   ```

---

## 4. Mapa con Fronteras (Scope Allowlist)
Solo se permite crear o modificar los siguientes archivos durante este átomo (cualquier archivo fuera de esta lista abortará el `Gate 0`):
- `src/Shared/Infrastructure/Symfony/ApiControllerExceptionListener.php`
- `src/Shared/Infrastructure/JsonTransformerExceptionListener.php`
- `config/services.yaml`
- `tests/Shared/Infrastructure/Symfony/ApiControllerExceptionListenerTest.php`

---

## 5. Silbato & Libro de Frases
- Si el oráculo de `gate.sh` falla **3 veces consecutivas** o supera **10 minutos** de bucle en la misma hipótesis -> el circuit breaker registrará `STATUS: BLOCKED` en `docs/stages/log.md`.
- Al completar con éxito el Gate (código de salida `0`), emite bloque YAML final con `STATUS: SEALED`:
```yaml
ATOM: atom-38-fix-api-controller-exception-listener
STAGE: stage-10
SPEC: FIX-01
STATUS: SEALED
```
