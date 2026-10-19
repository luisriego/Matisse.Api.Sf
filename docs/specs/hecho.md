# Specs de lo hecho

Cada spec de aquí tiene código en `src/Context`. Los átomos son la lista para el paso siguiente. El test sigue en **pendiente** hasta que se escriba.

## SPEC-TEN — Un condominio y un síndico

Estado: implementada.

Una base de datos es un condominio. Hay un solo `ROLE_SYNDIC`. El setup y la política de cobro usan un agregado de identidad fija.

Átomos previstos:

| ID | Nivel | Escenario |
|---|---|---|
| TEN-01 | unit | Promover a un usuario a síndico deja un único `ROLE_SYNDIC` |
| TEN-02 | integration | `SetSyndic` por email degrada al síndico anterior |
| TEN-03 | acceptance | Finalizar el setup en una base ya finalizada no vuelve a exigir el alta |

## SPEC-UNI — Unidades y fracción ideal

Estado: implementada.

Cada unidad tiene identidad, número y fracción ideal. La suma de fracciones no pasa de 1. La tolerancia es `1e-5` (`IdealFractionSumPolicy`).

Átomos previstos:

| ID | Nivel | Escenario |
|---|---|---|
| UNI-01 | unit | Una fracción que deja la suma en 1,00000005 se acepta |
| UNI-02 | unit | Una fracción que supera 1 más la tolerancia se rechaza |
| UNI-03 | integration | Crear unidad y cambiar su fracción persiste el valor |

## SPEC-MOV — Cuentas, gastos e ingresos

Estado: implementada. La ficha de gastos en `docs/BUSSINESS_RULES.md` está inferida del código. Ingresos y libro no tienen ficha ratificada; el comportamiento de abajo es el que el código hace hoy.

Importes en céntimos enteros. El cliente envía el UUID. Un gasto tiene monto y vencimiento. El tipo de reparto es `EQUAL`, `FRACTION` o `INDIVIDUAL`. Un ingreso tiene monto, tipo, cuenta y vencimiento. Una línea de extracto puede registrar el ingreso como ya cobrado y con vencimiento pasado.

Átomos previstos:

| ID | Nivel | Escenario |
|---|---|---|
| MOV-01 | unit | Un tipo distinto de `EQUAL`, `FRACTION` o `INDIVIDUAL` no se construye |
| MOV-02 | unit | `INDIVIDUAL` no entra en el pool común de la cuota |
| MOV-03 | integration | `PUT /api/v1/expenses/enter` responde 201 con el gasto creado |
| MOV-04 | integration | Registrar un ingreso del mes queda en la consulta por mes |
| MOV-05 | unit | Un gasto recurrente del mes genera el gasto de ese mes una vez |

`ExpenseEnterPutController` llama al handler y devuelve el cuerpo. No pasa por un bus de comandos sin respuesta.

## SPEC-GLM — Gas y política del mes

Estado: implementada.

El precio del metro cúbico, en céntimos, es `(2 × factura × (100 + buffer)) / (kg × 100)`, redondeado. También se puede fijar el precio directo. La lectura queda asociada a la unidad y al mes.

Si hay snapshot de política para el mes, manda sobre los parámetros del comando de generación: cuota extra por unidad, fondo de reserva por unidad, parte del síndico y precio del gas. Sin política, la parte del síndico por defecto es 60.000 céntimos. La regla de reparto guardada en el setup puede ser `equal_parts` o `ideal_fraction`; cuál usa la generación cuando ambas existen está en [por-hacer.md](por-hacer.md).

La línea `PF1SE` se parte en código: hasta 60.000 céntimos al pool igual del síndico, y el resto (internet/CFTV) a la unidad `401`.

Átomos previstos:

| ID | Nivel | Escenario |
|---|---|---|
| GLM-01 | unit | Factura, kilos y buffer producen el precio por m³ de la fórmula |
| GLM-02 | unit | Con snapshot del mes, la generación ignora la cuota extra enviada en el comando |
| GLM-03 | unit | `PF1SE` de 67.000 céntimos deja 60.000 en el pool del síndico y 7.000 en la unidad 401 |
| GLM-04 | integration | Registrar la política de un mes y resolverla devuelve esos céntimos |

## SPEC-INF — Informar el valor de cada unidad

Estado: ratificada el 18 oct 2026. Implementada en `SlipComponentBreakdownService`, `SlipGenerationPolicy` y `SlipFactory`.

La cuota informa el importe de cada unidad. La generación usa los gastos del mes N y el vencimiento cae en el mes N+1.

Calendario, salvo `force`:

- La primera generación abre el día 25 del mes de gasto.
- La recreación cierra el día 5 del mes de vencimiento.
- Pasado ese día, la primera generación de un mes saltado pide confirmación.
- `force` salta las reglas de calendario.

Reparto:

- `EQUAL`: parte entera del pool entre las unidades.
- `FRACTION`: parte entera de `pool × fracción / suma de fracciones`.
- `INDIVIDUAL`: importe de esa unidad, fuera del pool común.
- Encima: cuota extra, fondo de reserva, parte del síndico y gas.

Pico, ratificado:

- Si el resto de la división es 0, cada unidad se queda con la parte entera y la suma del pool coincide con el importe.
- Si el resto es mayor que 0, cada unidad paga un céntimo más. Con 5 unidades, un pico de 3 se sube a 5.
- La suma queda por encima del importe en `(unidades − pico)` céntimos.
- La regla vale para el pool igual y para el pool de fracción, por separado.
- Ejemplo: 103 céntimos entre 5. Cada unidad paga 21. La suma es 105.

Átomos previstos:

| ID | Nivel | Escenario |
|---|---|---|
| INF-01 | unit | Pool igual de 100 céntimos entre 5 unidades: 20 cada una, suma 100 |
| INF-02 | unit | Pool igual de 103 céntimos entre 5: 21 cada una, suma 105 |
| INF-03 | unit | Pool de fracción con pico: el mismo céntimo de más en cada unidad, no solo en las de mayor fracción |
| INF-04 | unit | Antes del día 25 la primera generación se rechaza |
| INF-05 | unit | Después del día 5 la recreación se rechaza |
| INF-06 | unit | `force` genera fuera de esa ventana |
| INF-07 | acceptance | Explicar el mes devuelve el desglose por unidad con la misma suma que generar |

Fuera de esta spec: boleto, baja y vencido. Ver SPEC-BOL.

## SPEC-CIE — Cierre de periodo

Estado: implementada.

Cerrar un mes de gasto exige que existan cuotas del mes de vencimiento (el mes siguiente) y que ese mes de gasto no esté cerrado. Un mes cerrado no admite otra generación (`PeriodClosureGuard`). El cierre emite `PeriodWasClosed`.

Átomos previstos:

| ID | Nivel | Escenario |
|---|---|---|
| CIE-01 | unit | Cerrar un mes sin cuotas lanza `NoSlipsToCloseException` |
| CIE-02 | unit | Cerrar un mes ya cerrado lanza `PeriodAlreadyClosedException` |
| CIE-03 | integration | Tras cerrar, generar ese mes vuelve a fallar |

## SPEC-BAN — Extracto OFX

Estado: implementada.

Una línea importada referencia un gasto o un ingreso, exactamente uno. La clave de línea evita importarla dos veces.

Átomos previstos:

| ID | Nivel | Escenario |
|---|---|---|
| BAN-01 | unit | Una línea sin gasto ni ingreso, o con los dos, no se construye |
| BAN-02 | integration | Confirmar la misma clave de línea por segunda vez no crea otro movimiento |

## SPEC-LEC — Previsión y libro

Estado: implementada como lectura. La promesa de qué número debe cuadrar con la cuota no está ratificada; no se añaden átomos de negocio hasta SPEC-LEC-PROMESA en [por-hacer.md](por-hacer.md).

Hoy el libro lista gastos e ingresos activos del mes, con filtro opcional por cuenta. La previsión arma el mes objetivo a partir de la conciliación y de la política.

Átomos previstos, solo de contrato técnico:

| ID | Nivel | Escenario |
|---|---|---|
| LEC-01 | integration | El libro de un mes sin movimientos devuelve la lista vacía |
| LEC-02 | integration | La previsión de un mes responde el mes objetivo pedido |
