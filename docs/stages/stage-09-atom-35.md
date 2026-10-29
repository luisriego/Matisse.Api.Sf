# Atom 35 — OPT-03: Estandarización del ciclo de vida de Eventos de Dominio y persistencia atómica en Outbox

## Metadata

| Campo | Valor |
|-------|-------|
| **ID** | `atom-35-opt-03-outbox-event-lifecycle-standardization` |
| **Stage** | stage-09 |
| **Spec Atom** | OPT-03 |
| **Level** | integration |
| **Mode** | characterization |
| **Covers** | `src/Context/Expense/Application/UseCase/EnterExpense/CreateRecurringExpenseCommandHandler.php` |
| **Test File** | `tests/Context/Expense/Application/UseCase/EnterExpense/CreateRecurringExpenseCommandHandlerTest.php` |

## Contexto

El bounded context Expense genera eventos de dominio en múltiples casos de uso. Estos eventos deben ser estandarizados en su ciclo de vida y persistidos atómicamente en la tabla outbox.

## Given / When / Then

- **Given:** eventos de dominio generados en múltiples casos de uso del bounded context Expense
- **When:** se estandariza el ciclo de vida de eventos y se persisten atómicamente en la tabla outbox
- **Then:** los tests de caracterización pasan y el outbox registra todos los eventos con secuencia consistente

## Allowlist

```
src/Context/Expense/Application/UseCase/EnterExpense/CreateRecurringExpenseCommandHandler.php
tests/Context/Expense/Application/UseCase/EnterExpense/CreateRecurringExpenseCommandHandlerTest.php
```

## Criterios de Aceptación

1. El test de caracterización `CreateRecurringExpenseCommandHandlerTest` pasa sin modificaciones al comportamiento existente.
2. Los eventos de dominio generados por `CreateRecurringExpenseCommandHandler` se persisten en la tabla outbox.
3. La persistencia del evento y la operación del caso de uso son atómicas (misma transacción).

## Pasos de Ejecución

1. Leer el test existente: `tests/Context/Expense/Application/UseCase/EnterExpense/CreateRecurringExpenseCommandHandlerTest.php`
2. Ejecutar el test para verificar estado actual
3. Si el test falla, analizar qué necesita el código para pasar
4. Implementar cambios mínimos en `CreateRecurringExpenseCommandHandler.php`
5. Re-ejecutar gate: `./tools/cleanroom/gate.sh stage-09 atom-35-opt-03-outbox-event-lifecycle-standardization`
