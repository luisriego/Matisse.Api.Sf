# atom-33-opt-01-doctrine-schema-validation-and-unidirectional-mappings

## OPT-01: Corrección de asociaciones Doctrine bidireccionales inválidas y validación de esquema

**Nivel:** integration | **Modo:** tdd

### Given
RecurringExpense, Income y Slip con atributos `inversed-by` inválidos en sus asociaciones ManyToOne entre Bounded Contexts.

### When
Se convierten las asociaciones foráneas en unidireccionales eliminando `inversed-by` de los mappings ORM XML.

### Then
El schema validation test pasa sin errores de mapeo y no hay associations cruzadas entre contextos.

### Archivos afectados
- `src/Context/Expense/Infrastructure/Persistence/Doctrine/RecurringExpense.orm.xml`
- `src/Context/Income/Infrastructure/Persistence/Doctrine/Income.orm.xml`
- `src/Context/Slip/Infrastructure/Persistence/Doctrine/Slip.orm.xml`
- `tests/Shared/Infrastructure/Persistence/Doctrine/DoctrineSchemaValidationIntegrationTest.php`

### Criterios de aceptación
1. Ningún ManyToOne tiene `inversed-by` en los mappings XML
2. El SchemaValidator de Doctrine no reporta errores de mapping
3. No hay asociaciones cruzadas entre Bounded Contexts (sin FK entre contextos)
