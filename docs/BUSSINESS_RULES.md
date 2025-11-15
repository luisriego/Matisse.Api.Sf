# Reglas de Negocio Inviolables

Este documento contiene las reglas de negocio fundamentales del proyecto que deben ser respetadas en todo momento. Han sido inferidas a partir del código fuente y la estructura del proyecto.

## 1. Entidad Gasto (`Expense`)

Estas reglas definen la estructura y las propiedades de un gasto individual.

-   **1.1. Identificación Única**: Cada gasto (`Expense`) se identifica de forma única mediante un `id` de tipo UUID.
-   **1.2. Datos Fundamentales**: Todo gasto debe tener:
    -   Un `amount` (monto) de tipo entero.
    -   Una `dueDate` (fecha de vencimiento).
-   **1.3. Datos Opcionales**: Un gasto puede tener:
    -   Una `description` (descripción) en formato de texto.
    -   Una `paidAt` (fecha de pago). Una vez establecida, no debería cambiar (inmutable).
    -   Un `attachment` (adjunto), que es la ruta a un fichero.
-   **1.4. Metadatos**:
    -   Cada gasto registra su `createdAt` (fecha de creación), que es inmutable.
    -   Cada gasto tiene un estado `isActive` (activo/inactivo), que es un booleano.
-   **1.5. Relaciones**:
    -   Un gasto puede estar asociado a una `Account` (cuenta).
    -   Un gasto puede estar asociado a un `ExpenseType` (tipo de gasto).
    -   Un gasto puede estar asociado a un `RecurringExpense` (gasto recurrente).
    -   Un gasto puede estar asociado a una `residentUnitId` (ID de unidad residencial).

## 2. Casos de Uso y Lógica de Negocio (API)

Estas reglas definen cómo se interactúa con los gastos y otras entidades relacionadas a través de la API.

### 2.1. Gestión de Gastos

-   **2.1.1. Creación**: Se pueden registrar nuevos gastos en el sistema, ya sea con o sin descripción.
-   **2.1.2. Actualización**: La información de un gasto existente puede ser modificada.
-   **2.1.3. Pago**: Un gasto puede ser marcado como "pagado", registrando la fecha del pago.
-   **2.1.4. Compensación**: Existe una operación para "compensar" un gasto.
-   **2.1.5. Adjuntos**: Se puede añadir o modificar un archivo adjunto a un gasto.

### 2.2. Gestión de Gastos Recurrentes

-   **2.2.1. CRUD**: Se pueden crear, leer, actualizar y eliminar (`CRUD`) gastos recurrentes (`RecurringExpense`).
-   **2.2.2. Registro Mensual**: Existe un proceso para registrar automáticamente los gastos recurrentes que corresponden a un mes específico.

### 2.3. Consultas

-   **2.3.1. Consulta Individual**: Se puede obtener un gasto específico por su `id`.
-   **2.3.2. Consulta General**: Se puede obtener una lista de todos los gastos.
-   **2.3.3. Consulta por Fechas**: Se pueden obtener gastos (activos o inactivos) filtrando por un rango de fechas (año y mes).
-   **2.3.4. Consulta de Recurrentes**: Se pueden consultar los gastos recurrentes pendientes para un mes y año, así como todos los de un año específico.
-   **2.3.5. Consulta de Tipos de Gasto**: Se puede obtener la lista de todos los `ExpenseType` disponibles.

### 2.4. Servicios Externos

-   **2.4.1. Extracción de Datos de Facturas**: El sistema utiliza un servicio de Inteligencia Artificial (Google Cloud Document AI) para extraer automáticamente información relevante de los archivos de facturas que se adjuntan.
