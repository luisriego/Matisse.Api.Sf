# Tarjeta de Etapa: stage-10 — Átomo FIX-02 (`atom-39-complete-domain-event-registry-map`)

---

## 1. Peregrino & Mochila
- **Objetivo Atómico (SRP)**: FIX-02: Registro completo de los 28 Eventos de Dominio en DomainEventRegistryMap para reconstrucción desde EventStore
- **Stage**: `stage-10` — SPEC-FIX-INFRA: Corrección de Infraestructura HTTP, Eventos de Dominio y Runtime Bugs
- **Spec**: `SPEC-FIX-INFRA` (`FIX-02`) | **Nivel**: `unit` | **Modo**: `tdd`
- **Rama dedicada**: `atom-39-complete-domain-event-registry-map` (consolidación fast-forward en `stage-10`).
- **TDD Innegociable**: `RED -> GREEN -> REFACTOR -> GATE -> SELLO`.

---

## 2. Botas (Contexto & Especificación)
- **Artefacto principal bajo cobertura**: `src/Context/EventStore/Infrastructure/DomainEventRegistryMap.php`
- **Archivo de prueba objetivo**: `tests/Context/EventStore/Infrastructure/DomainEventRegistryMapCompletenessTest.php`
- **Contrato BDD Comprobable**:
  - **Dado (Given)**: todas las clases concretas que extienden App\Shared\Domain\Event\DomainEvent en src/Context/*/Domain/Event/*.php
  - **Cuando (When)**: se consulta DomainEventRegistryMap::getClassForEventType($eventClass::eventName()) para cada evento del sistema
  - **Entonces (Then)**: devuelve exactamente el FQCN de cada uno de los 28 eventos de dominio sin retornar null en ninguno
- **Casos de prueba obligatorios en `tests/Context/EventStore/Infrastructure/DomainEventRegistryMapCompletenessTest.php`**:
  1. `testAllDomainEventsInCodebaseAreRegisteredInMap`: Escanea `src/Context/*/Domain/Event/*.php`, obtiene el FQCN de cada subclase concreta de `DomainEvent`, invoca `$eventClass::eventName()` y verifica que `DomainEventRegistryMap::getClassForEventType()` devuelva exactamente `$eventClass`.
  2. `testUnknownEventTypeReturnsNull`: Verifica que `getClassForEventType('unknown.event')` devuelva `null`.

---

## 3. Ropa (Modus Operandi)
1. **Test (RED / Characterization)**:
   - Crea o actualiza `tests/Context/EventStore/Infrastructure/DomainEventRegistryMapCompletenessTest.php` con los casos de prueba obligatorios de la sección 2.
   - Ejecuta el oráculo de tests:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/EventStore/Infrastructure/DomainEventRegistryMapCompletenessTest.php
     ```
2. **Implementación (GREEN)**:
   - En `src/Context/EventStore/Infrastructure/DomainEventRegistryMap.php`: registrar los 15 eventos faltantes además de los 13 actuales: `AccountDetailsWereUpdated`, `IncomeWasPaid`, `IncomeWasCategorized`, `IncomeDueDateWasChanged`, `IncomeDescriptionWasChanged`, `ResidentUnitIdealFractionWasChanged`, `ResidentUnitRecipientWasAppended`, `ResidentUnitRecipientsWereReplaced`, `PeriodWasClosed`, `SlipWasSubmitted`, `SlipWasPaid`, `SlipWasMarkedAsOverdue`, `SlipWasCancelled`, `SlipWasImported` y `UserWasRegistered`.
   - Vuelve a ejecutar PHPUnit y verifica verde:
     ```bash
     ./tools/cleanroom/exec_php.sh vendor/bin/phpunit tests/Context/EventStore/Infrastructure/DomainEventRegistryMapCompletenessTest.php
     ```
   - Ejecuta análisis estático y formateador sobre los archivos tocados:
     ```bash
     ./tools/cleanroom/exec_php.sh tools/php-cs-fixer/vendor/bin/php-cs-fixer fix src/Context/EventStore/Infrastructure/DomainEventRegistryMap.php tests/Context/EventStore/Infrastructure/DomainEventRegistryMapCompletenessTest.php
     ./tools/cleanroom/exec_php.sh vendor/bin/phpstan analyse src/Context/EventStore/Infrastructure/DomainEventRegistryMap.php tests/Context/EventStore/Infrastructure/DomainEventRegistryMapCompletenessTest.php --memory-limit=512M
     ```
3. **Gate & Sello**:
   ```bash
   ./tools/cleanroom/gate.sh stage-10 atom-39-complete-domain-event-registry-map --auto-commit
   ```

---

## 4. Mapa con Fronteras (Scope Allowlist)
Solo se permite crear o modificar los siguientes archivos durante este átomo (cualquier archivo fuera de esta lista abortará el `Gate 0`):
- `src/Context/EventStore/Infrastructure/DomainEventRegistryMap.php`
- `tests/Context/EventStore/Infrastructure/DomainEventRegistryMapCompletenessTest.php`

---

## 5. Silbato & Libro de Frases
- Si el oráculo de `gate.sh` falla **3 veces consecutivas** o supera **10 minutos** de bucle en la misma hipótesis -> el circuit breaker registrará `STATUS: BLOCKED` en `docs/stages/log.md`.
- Al completar con éxito el Gate (código de salida `0`), emite bloque YAML final con `STATUS: SEALED`:
```yaml
ATOM: atom-39-complete-domain-event-registry-map
STAGE: stage-10
SPEC: FIX-02
STATUS: SEALED
```
