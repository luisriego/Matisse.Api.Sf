# Specs por hacer

Nada de esta lista se parte en tests hasta que deje de estar bloqueada o descartada. El código de cobro que ya existe no convierte estas filas en hecho.

## SPEC-BOL — Boleto, baja y vencido

Estado: bloqueada. Ratificada como trabajo futuro el 18 oct 2026.

Hoy la cuota informa el valor. Cuando exista la API del banco, el sistema generará el boleto y lo dará de baja. Overdue es un boleto vencido. Con el banco actual no se sabe quién está impago, así que no se opera el cobro ni el vencido.

En código hay una máquina `pending → submitted → paid / overdue / cancelled`. No es el contrato actual. No se añaden transiciones nuevas, ni `pay` desde `overdue`, ni estados `validated`, `posted` o `rejected`.

Átomos: ninguno hasta tener el contrato del banco (alta del boleto, baja, e identificador de impago).

Descartada en el mismo alcance: el circuito de aprobación de `docs/NEXTSTEPS.md`.

## SPEC-ABR — Decisiones que aún bloquean ficha

Estado: bloqueada. Cada fila es una pregunta, no una regla.

| ID | Pregunta | Mientras tanto el código |
|---|---|---|
| ABR-01 | Si la parte del síndico se reparte `equal_parts` o `ideal_fraction` cuando el setup guardó una regla y la política trae el total | La generación reparte ese pool en partes iguales |
| ABR-02 | Quién puede usar `force` en un mes pasado y si queda auditoría del actor | `force` salta el calendario |
| ABR-03 | Si `paidAt` de un gasto, una vez puesto, se puede corregir | `docs/BUSSINESS_RULES.md` lo trata como inmutable; falta el test que lo fije o la decisión de permitir la corrección |
| ABR-04 | Cuándo el transporte `async` deja de ser `sync://` | El envío masivo corre en el mismo proceso |

Sin átomos hasta cerrar la fila.

## SPEC-LEC-PROMESA — Qué prometen la previsión y el libro

Estado: bloqueada.

SPEC-LEC cubre que las lecturas responden. Falta decidir si el total previsto de una unidad es el mismo importe que SPEC-INF calcularía para ese mes, y si el libro es la suma de gastos e ingresos o incluye las cuotas.

Átomos: ninguno hasta escribir esa igualdad.

## SPEC-RAT — Fichas inferidas que conviene ratificar

Estado: bloqueada como texto de negocio. El código ya está y SPEC-MOV lo describe como implementado.

Ratificar, sin cambiar comportamiento, estas frases antes de darlas por regla inviolable:

- Datos obligatorios y opcionales del ingreso, incluidos vencimiento pasado y cobro inmediato cuando la línea viene del banco.
- Unicidad del gasto recurrente al registrarlo en un mes.
- La convención `PF1SE`: 60.000 céntimos al síndico y el resto a la unidad 401.

Cuando una frase se ratifique, su átomo pasa a la spec de [hecho.md](hecho.md) correspondiente.
