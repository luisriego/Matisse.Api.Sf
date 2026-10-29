# atom-36-opt-04-retry-mechanism-for-external-service-calls

## OPT-04: Mecanismo de reintento para llamadas a servicios externos en importación bancaria

**Nivel:** integration | **Modo:** tdd

### Given
Llamadas a servicios externos (OFX, APIs bancarias) que pueden fallar intermitentemente durante la importación de estados de cuenta.

### When
Se implementa un mecanismo de reintento con backoff exponencial y límite de intentos en `ConfirmBankOfxLinesCommandHandler`.

### Then
Los tests verifican que se reintenta correctamente ante fallos transitorios y se propaga la excepción tras agotar los intentos.

### Archivos afectados
- `src/Context/BankStatement/Application/UseCase/ConfirmBankOfxLines/ConfirmBankOfxLinesCommandHandler.php`
- `tests/Context/BankStatement/Application/UseCase/ConfirmBankOfxLines/ConfirmBankOfxLinesCommandHandlerTest.php`

### Criterios de aceptación
1. El handler implementa reintento con backoff exponencial (máximo 3 intentos)
2. Los fallos transitorios se reintentan, los errores definitivos se propagan inmediatamente
3. Los tests de integración verifican el comportamiento de reintento
