# Catálogo Completo de Fases y Átomos (`Matisse.Api.Sf`)

Este catálogo desglosa las especificaciones de [`hecho.md`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/docs/specs/hecho.md) y los estándares transversales de [`TESTING-STANDARD-INDUSTRIA.md`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/docs/specs/TESTING-STANDARD-INDUSTRIA.md) en **8 Fases (`stage-01` a `stage-08`)** y **32 Átomos ejecutables**, respetando estrictamente la regla de [`README.md`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/docs/specs/README.md):

1. Empezar por **`SPEC-INF`** (ratificada el 18 oct 2026).
2. Cubrir todos los átomos de **`hecho.md`** (`SPEC-INF`, `SPEC-TEN`, `SPEC-UNI`, `SPEC-MOV`, `SPEC-GLM`, `SPEC-CIE`, `SPEC-BAN`, `SPEC-LEC`).
3. Completar todos los niveles de prueba estándar en la industria (**Unit, Architecture, Integration, Security, Acceptance/E2E, Contract/OpenAPI, Mutation**).
4. **No abrir ningún átomo de [`por-hacer.md`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/docs/specs/por-hacer.md)** mientras su fila siga en estado `bloqueada` o `descartada`.

---

## Mapa General de Etapas (`stages.json`)

| Etapa | Nombre | Spec(s) Cubiertas | Átomos | Niveles de Test |
|---|---|---|---|---|
| **`stage-01`** | Informar el valor de cada unidad (`SPEC-INF`) | `SPEC-INF` (`INF-01`..`INF-07`) | `atom-01` a `atom-07` | `unit`, `acceptance`, `mutation` |
| **`stage-02`** | Un condominio y un síndico (`SPEC-TEN`) | `SPEC-TEN` (`TEN-01`..`TEN-03`) | `atom-08` a `atom-10` | `unit`, `integration`, `acceptance` |
| **`stage-03`** | Unidades y fracción ideal (`SPEC-UNI`) | `SPEC-UNI` (`UNI-01`..`UNI-03`) | `atom-11` a `atom-13` | `unit`, `integration`, `mutation` |
| **`stage-04`** | Cuentas, gastos e ingresos (`SPEC-MOV`) | `SPEC-MOV` (`MOV-01`..`MOV-05`) | `atom-14` a `atom-18` | `unit`, `integration` |
| **`stage-05`** | Gas y política del mes (`SPEC-GLM`) | `SPEC-GLM` (`GLM-01`..`GLM-04`) | `atom-19` a `atom-22` | `unit`, `integration`, `mutation` |
| **`stage-06`** | Cierre de periodo (`SPEC-CIE`) | `SPEC-CIE` (`CIE-01`..`CIE-03`) | `atom-23` a `atom-25` | `unit`, `integration`, `mutation` |
| **`stage-07`** | Extracto OFX, Previsión y Libro (`SPEC-BAN`, `SPEC-LEC`) | `SPEC-BAN` (`BAN-01`..`BAN-02`), `SPEC-LEC` (`LEC-01`..`LEC-02`) | `atom-26` a `atom-29` | `unit`, `integration` |
| **`stage-08`** | Blindaje Industrial: Higiene de Suites, Arquitectura, Seguridad y Contratos OpenAPI | Transversal (Industry Standard) | `atom-30` a `atom-32` | `architecture`, `security`, `contract` |

---

## Etapa 1 (`stage-01`) — `SPEC-INF`: Informar el valor de cada unidad

> **Estado de la Spec**: **Ratificada** (18 oct 2026).

### `atom-01-inf-01-equal-pool-exact-division` (`INF-01`)
- **Spec**: `SPEC-INF` | **Nivel**: `unit` | **Modo**: `characterization`
- **Dado**: 5 unidades activas (`101`, `201`, `301`, `401`, `501`) y un pool igualitario (`baseEqualPoolCents`) de `100` céntimos (resto `0`), con los demás pools en `0`.
- **Cuando**: Se calcula el desglose con `SlipComponentBreakdownService::build(...)`.
- **Entonces**: Cada unidad recibe exactamente `20` céntimos en `baseEqualShareCents` y `totalCents`, y `components.baseTotalCents` y `totals.totalCents` valen `100`.
- **Cubre**: [`SlipComponentBreakdownService`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Slip/Domain/Service/SlipComponentBreakdownService.php)
- **Test**: `tests/Context/Slip/Domain/Service/SlipComponentBreakdownServiceTest.php`

### `atom-02-inf-02-equal-pool-pico-raises-all-units` (`INF-02`)
- **Spec**: `SPEC-INF` | **Nivel**: `unit` | **Modo**: `characterization`
- **Dado**: 5 unidades activas y un pool igualitario (`baseEqualPoolCents`) de `103` céntimos (parte entera `20`, pico/resto `3 > 0`).
- **Cuando**: Se calcula el desglose con `SlipComponentBreakdownService::build(...)`.
- **Entonces**: Cada una de las 5 unidades paga `21` céntimos (`20 + 1`), la suma del pool (`baseTotalCents`) es `105` (superando el importe en `5 - 3 = 2` céntimos) y `totals.differenceCents` es `0`.
- **Cubre**: [`SlipComponentBreakdownService`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Slip/Domain/Service/SlipComponentBreakdownService.php)
- **Test**: `tests/Context/Slip/Domain/Service/SlipComponentBreakdownServiceTest.php`

### `atom-03-inf-03-fraction-pool-pico-raises-all-units` (`INF-03`)
- **Spec**: `SPEC-INF` | **Nivel**: `unit` | **Modo**: `characterization`
- **Dado**: 5 unidades con fracciones ideales `[0.10, 0.10, 0.10, 0.10, 0.20]` (o suma `1.0` con división inexacta) y un pool por fracción (`fractionPoolCents`) cuyo reparto deja resto $> 0$ (p. ej. `10` céntimos con fracciones `[0.10, 0.10, 0.10, 0.10, 0.20]` donde `floor` suma `1+1+1+1+3 = 7`, resto `3 > 0`).
- **Cuando**: Se calcula el desglose con `SlipComponentBreakdownService::build(...)`.
- **Entonces**: Se suma `+1` céntimo a **todas** las unidades por igual (`[2, 2, 2, 2, 4]`, suma `12`), y no únicamente a las unidades de mayor fracción ideal.
- **Cubre**: [`SlipComponentBreakdownService`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Slip/Domain/Service/SlipComponentBreakdownService.php)
- **Test**: `tests/Context/Slip/Domain/Service/SlipComponentBreakdownServiceTest.php`

### `atom-04-inf-04-first-generation-before-day-25-rejected` (`INF-04`)
- **Spec**: `SPEC-INF` | **Nivel**: `unit` | **Modo**: `characterization`
- **Dado**: No existen cuotas previas para el mes de vencimiento $N+1$, `isForced = false`, y el reloj (`Clock`) está antes del día 25 del mes de gasto $N$ (ej. `2026-06-24`).
- **Cuando**: Se invoca `SlipGenerationPolicy::check(2026, 6, false)`.
- **Entonces**: Lanza `GenerationNotAllowedYetException`.
- **Cubre**: [`SlipGenerationPolicy`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Slip/Domain/Service/SlipGenerationPolicy.php)
- **Test**: `tests/Context/Slip/Domain/Service/SlipGenerationPolicyTest.php`

### `atom-05-inf-05-recreation-after-day-5-rejected` (`INF-05`)
- **Spec**: `SPEC-INF` | **Nivel**: `unit` | **Modo**: `characterization`
- **Dado**: Ya existen cuotas para el mes de vencimiento $N+1$ (`existsForDueDateMonth === true`), `isForced = false`, y el reloj (`Clock`) está después del día 5 del mes de vencimiento $N+1$ (ej. `2026-07-06`).
- **Cuando**: Se invoca `SlipGenerationPolicy::check(2026, 6, false)`.
- **Entonces**: Lanza `RecreationExpiredException` (y si no existían cuotas previas después del día 5, lanza `PastMonthGenerationRequiresConfirmationException`).
- **Cubre**: [`SlipGenerationPolicy`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Slip/Domain/Service/SlipGenerationPolicy.php)
- **Test**: `tests/Context/Slip/Domain/Service/SlipGenerationPolicyTest.php`

### `atom-06-inf-06-force-bypasses-calendar-window` (`INF-06`)
- **Spec**: `SPEC-INF` | **Nivel**: `unit` | **Modo**: `characterization`
- **Dado**: Reloj fuera de ventana (antes del día 25 en primera generación o después del día 5 en recreación o mes saltado) con `isForced = true`.
- **Cuando**: Se invoca `SlipGenerationPolicy::check(year, month, true)`.
- **Entonces**: No lanza excepción y permite la generación sin siquiera consultar `existsForDueDateMonth`.
- **Cubre**: [`SlipGenerationPolicy`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Slip/Domain/Service/SlipGenerationPolicy.php)
- **Test**: `tests/Context/Slip/Domain/Service/SlipGenerationPolicyTest.php`

### `atom-07-inf-07-explain-matches-generated-slips-sum` (`INF-07`)
- **Spec**: `SPEC-INF` | **Nivel**: `acceptance` | **Modo**: `tdd`
- **Dado**: Un condominio con unidades activas, cuenta activa, gastos del mes $N$ (`EQUAL`, `FRACTION`, `INDIVIDUAL`) y política/parámetros de generación.
- **Cuando**: Se consulta `GET /api/v1/slips/generation/explain?targetMonth=YYYY-MM&extraFeePerUnitCents=X&reserveFundPerUnitCents=Y` y se ejecuta `POST /api/v1/slips/generate` para ese mismo mes $(N)$ y parámetros, seguido de `GET /api/v1/slips/{dueYear}/{dueMonth}`.
- **Entonces**: El desglose por unidad devuelto por `explain` (`units[*].totalCents`) tiene exactamente los mismos importes por unidad y la misma suma total (`totals.totalCents`) que las cuotas persistidas por `generate`.
- **Cubre**: [`ExplainSlipGenerationGetController`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Slip/Infrastructure/Http/Controller/ExplainSlipGenerationGetController.php), [`SlipGenerationPostController`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Slip/Infrastructure/Http/Controller/SlipGenerationPostController.php), [`FindSlipsByMonthGetController`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Slip/Infrastructure/Http/Controller/FindSlipsByMonthGetController.php)
- **Test**: `tests/Acceptance/Slip/ExplainMatchesGeneratedSlipsAcceptanceTest.php`

---

## Etapa 2 (`stage-02`) — `SPEC-TEN`: Un condominio y un síndico

### `atom-08-ten-01-promote-syndic-single-role` (`TEN-01`)
- **Spec**: `SPEC-TEN` | **Nivel**: `unit` | **Modo**: `characterization`
- **Dado**: Un agregado `User` residente y otro `User` síndico.
- **Cuando**: Se invoca `promoteToSyndic()` sobre el usuario (incluso múltiples veces) y `demoteToResident()` sobre el anterior.
- **Entonces**: El nuevo síndico tiene un único `ROLE_SYNDIC` sin duplicados en `getRoles()` (`isSyndic() === true`) y el anterior pierde `ROLE_SYNDIC` conservando `ROLE_USER` (`isSyndic() === false`).
- **Cubre**: [`User`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/User/Domain/User.php)
- **Test**: `tests/Context/User/Domain/UserTest.php`

### `atom-09-ten-02-set-syndic-demotes-previous` (`TEN-02`)
- **Spec**: `SPEC-TEN` | **Nivel**: `integration` | **Modo**: `tdd`
- **Dado**: Dos usuarios persistidos en PostgreSQL donde el usuario A tiene `ROLE_SYNDIC` y el usuario B es residente.
- **Cuando**: Se ejecuta el comando de consola `app:syndic:set <email-B>` (`SetSyndicCommand`).
- **Entonces**: El comando termina con `Command::SUCCESS`, el usuario B pasa a tener `ROLE_SYNDIC`, el usuario A es degradado a residente (`isSyndic() === false`), y en toda la base de datos queda un único usuario en `findSyndics()`.
- **Cubre**: [`SetSyndicCommand`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/User/Infrastructure/Console/SetSyndicCommand.php), [`DoctrineUserRepository`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/User/Infrastructure/Persistence/Doctrine/DoctrineUserRepository.php)
- **Test**: `tests/Context/User/Infrastructure/Console/SetSyndicCommandTest.php`

### `atom-10-ten-03-finalize-setup-idempotent` (`TEN-03`)
- **Spec**: `SPEC-TEN` | **Nivel**: `acceptance` | **Modo**: `characterization`
- **Dado**: Una base de datos con el setup ya finalizado (`POST /api/v1/setup/finalize`).
- **Cuando**: Se vuelve a invocar `POST /api/v1/setup/finalize` y se consulta `GET /api/v1/setup/status`.
- **Entonces**: Responde `200 OK` con `alreadyFinalized: true` (sin error ni duplicar el evento `setup.was.completed`) y `GET /api/v1/setup/status` mantiene `isFinalized: true` sin volver a exigir el alta.
- **Cubre**: [`SetupFinalizePostController`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Setup/Infrastructure/Http/Controller/SetupFinalizePostController.php), [`SetupFinalizationService`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Setup/Application/Service/SetupFinalizationService.php)
- **Test**: `tests/Context/Setup/Infrastructure/Http/Controller/SetupFinalizePostControllerTest.php`

---

## Etapa 3 (`stage-03`) — `SPEC-UNI`: Unidades y fracción ideal

### `atom-11-uni-01-fraction-sum-within-tolerance-accepted` (`UNI-01`)
- **Spec**: `SPEC-UNI` | **Nivel**: `unit` | **Modo**: `characterization`
- **Dado**: Una suma acumulada de fracciones ideales que con la nueva unidad queda en `1.00000005` (dentro de la tolerancia `1e-5` de `IdealFractionSumPolicy`).
- **Cuando**: Se evalúa `IdealFractionSumPolicy::exceedsMaximum(1.00000005)` y `ResidentUnitValidator`.
- **Entonces**: Se acepta (`exceedsMaximum === false`, `isWithinMaximum === true`) sin lanzar excepción.
- **Cubre**: [`IdealFractionSumPolicy`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/ResidentUnit/Domain/IdealFractionSumPolicy.php), [`ResidentUnitValidator`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/ResidentUnit/Domain/ResidentUnitValidator.php)
- **Test**: `tests/Context/ResidentUnit/Domain/IdealFractionSumPolicyTest.php`

### `atom-12-uni-02-fraction-sum-above-tolerance-rejected` (`UNI-02`)
- **Spec**: `SPEC-UNI` | **Nivel**: `unit` | **Modo**: `characterization`
- **Dado**: Una fracción ideal adicional que hace que la suma supere `1.0 + 1e-5` (ej. `1.00002`).
- **Cuando**: Se evalúa `IdealFractionSumPolicy` o se valida la creación/actualización en `CreateResidentUnitCommandHandler` / `PatchIdealFractionCommandHandler`.
- **Entonces**: Se rechaza (`exceedsMaximum === true` y el handler/validador lanza la excepción de dominio correspondiente).
- **Cubre**: [`IdealFractionSumPolicy`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/ResidentUnit/Domain/IdealFractionSumPolicy.php), [`PatchIdealFractionCommandHandler`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/ResidentUnit/Application/UseCase/PatchIdealFraction/PatchIdealFractionCommandHandler.php)
- **Test**: `tests/Context/ResidentUnit/Domain/IdealFractionSumPolicyTest.php`, `tests/Context/ResidentUnit/Application/UseCase/PatchIdealFraction/PatchIdealFractionCommandHandlerTest.php`

### `atom-13-uni-03-create-and-patch-unit-fraction-persists` (`UNI-03`)
- **Spec**: `SPEC-UNI` | **Nivel**: `integration` | **Modo**: `characterization`
- **Dado**: Un síndico autenticado.
- **Cuando**: Crea una unidad vía `PUT /api/v1/resident-unit/create`, modifica su fracción ideal vía `PATCH /api/v1/resident-unit/{id}/ideal-fraction` y la consulta vía `GET /api/v1/resident-unit/{id}`.
- **Entonces**: El nuevo valor de `idealFraction` queda persistido en PostgreSQL y se devuelve en la consulta.
- **Cubre**: [`ResidentUnitCreateController`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/ResidentUnit/Infrastructure/Http/Controller/ResidentUnitCreateController.php), [`ResidentUnitIdealFractionPatchController`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/ResidentUnit/Infrastructure/Http/Controller/ResidentUnitIdealFractionPatchController.php)
- **Test**: `tests/Context/ResidentUnit/Infrastructure/Http/Controller/ResidentUnitIdealFractionPatchControllerTest.php`

---

## Etapa 4 (`stage-04`) — `SPEC-MOV`: Cuentas, gastos e ingresos

### `atom-14-mov-01-expense-type-distribution-method-validation` (`MOV-01`)
- **Spec**: `SPEC-MOV` | **Nivel**: `unit` | **Modo**: `characterization`
- **Dado**: Un string de método de distribución distinto de `EQUAL`, `FRACTION` o `INDIVIDUAL`.
- **Cuando**: Se intenta construir `ExpenseTypeDistributionMethod`.
- **Entonces**: Lanza `InvalidArgumentException` y no permite instanciar el Value Object.
- **Cubre**: [`ExpenseTypeDistributionMethod`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Expense/Domain/ValueObject/ExpenseTypeDistributionMethod.php)
- **Test**: `tests/Context/Expense/Domain/ExpenseTypeAccountRelationTest.php`

### `atom-15-mov-02-individual-expense-excluded-from-common-pool` (`MOV-02`)
- **Spec**: `SPEC-MOV` | **Nivel**: `unit` | **Modo**: `characterization`
- **Dado**: Un gasto de tipo `INDIVIDUAL` (con o sin `residentUnitId`) junto a gastos `EQUAL` y `FRACTION`.
- **Cuando**: Se agrega el mes con `MonthlyExpenseAggregatorService::aggregateTotals(...)`.
- **Entonces**: El gasto `INDIVIDUAL` no suma en `equal` ni en `fraction`; solo se imputa a `individual` / `individualByUnit[$unitId]`.
- **Cubre**: [`MonthlyExpenseAggregatorService`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Slip/Domain/Service/MonthlyExpenseAggregatorService.php)
- **Test**: `tests/Context/Slip/Domain/Service/MonthlyExpenseAggregatorServiceTest.php`

### `atom-16-mov-03-put-expenses-enter-returns-201-with-body` (`MOV-03`)
- **Spec**: `SPEC-MOV` | **Nivel**: `integration` | **Modo**: `characterization`
- **Dado**: Una cuenta activa y un `ExpenseType` persistidos.
- **Cuando**: Se envía `PUT /api/v1/expenses/enter` con UUID, importe en céntimos y fecha de vencimiento.
- **Entonces**: `ExpenseEnterPutController` invoca directamente a `EnterExpenseCommandHandler` y responde `201 Created` con el JSON del gasto creado en el cuerpo y persiste `ExpenseWasEntered` en el `EventStore`.
- **Cubre**: [`ExpenseEnterPutController`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Expense/Infrastructure/Http/Controller/ExpenseEnterPutController.php)
- **Test**: `tests/Context/Expense/Infrastructure/Http/Controller/ExpenseEnterPutControllerTest.php`

### `atom-17-mov-04-enter-income-listed-in-month-query` (`MOV-04`)
- **Spec**: `SPEC-MOV` | **Nivel**: `integration` | **Modo**: `characterization`
- **Dado**: Una unidad residencial, una cuenta y un `IncomeType` persistidos.
- **Cuando**: Se registra un ingreso vía `PUT /api/v1/incomes/enter` con vencimiento en el año/mes $(Y, M)$ y luego se consulta `GET /api/v1/incomes/date-range/{Y}/{M}`.
- **Entonces**: La respuesta `200 OK` del mes contiene el ingreso registrado con su importe en céntimos y tipo.
- **Cubre**: [`IncomeEnterPutController`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Income/Infrastructure/Http/Controller/IncomeEnterPutController.php), [`GetIncomesByMonthController`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Income/Infrastructure/Http/Controller/GetIncomesByMonthController.php)
- **Test**: `tests/Context/Income/Infrastructure/Http/Controller/GetIncomesByMonthControllerTest.php`

### `atom-18-mov-05-monthly-recurring-expense-idempotent-once-per-month` (`MOV-05`)
- **Spec**: `SPEC-MOV` | **Nivel**: `unit` | **Modo**: `characterization`
- **Dado**: Un `RecurringExpense` activo aplicable al mes $M$.
- **Cuando**: Se registra el gasto del mes mediante `EnterMonthlyRecurringExpenseCommandHandler` (o se evalúa su contribución en `RecurringExpenseSlipContributionService` cuando ya existe un `Expense` reconciliado para ese `RecurringExpense` en el mes).
- **Entonces**: No duplica el gasto ni su contribución al pool del mes.
- **Cubre**: [`EnterMonthlyRecurringExpenseCommandHandler`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Expense/Application/UseCase/EnterExpense/EnterMonthlyRecurringExpenseCommandHandler.php), [`RecurringExpenseSlipContributionService`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Slip/Domain/Service/RecurringExpenseSlipContributionService.php)
- **Test**: `tests/Context/Slip/Domain/Service/RecurringExpenseSlipContributionServiceDedupTest.php`

---

## Etapa 5 (`stage-05`) — `SPEC-GLM`: Gas y política del mes

### `atom-19-glm-01-gas-price-formula-from-bill-kg-buffer` (`GLM-01`)
- **Spec**: `SPEC-GLM` | **Nivel**: `unit` | **Modo**: `characterization`
- **Dado**: Un importe de factura en céntimos (`billCents`), capacidad de cilindro en kg (`kg`) y porcentaje de buffer (`buffer`).
- **Cuando**: Se invoca `Gas::definePrice($amount, $capacity, $buffer)`.
- **Entonces**: Calcula el precio por $\text{m}^3$ en céntimos como $\text{round}\left(\frac{2 \times \text{factura} \times (100 + \text{buffer})}{\text{kg} \times 100}\right)$ y registra el evento `GasPriceWasDefined`.
- **Cubre**: [`Gas`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Gas/Domain/Gas.php)
- **Test**: `tests/Context/Gas/Domain/GasTest.php`

### `atom-20-glm-02-policy-snapshot-overrides-generation-command-params` (`GLM-02`)
- **Spec**: `SPEC-GLM` | **Nivel**: `unit` | **Modo**: `characterization`
- **Dado**: Un snapshot de política mensual (`ResolvedBillingPolicy` con `hasPolicy() === true`) que define `extraFeePerUnitCents`, `reserveFundPerUnitCents` y `syndicShareTotalCents`, y un `SlipGenerationCommand` que envía valores distintos.
- **Cuando**: Se ejecuta `SlipGenerationCommandHandler`.
- **Entonces**: La generación usa los valores del snapshot de política del mes e ignora la cuota extra y fondo de reserva enviados en el comando.
- **Cubre**: [`SlipGenerationCommandHandler`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Slip/Application/UseCase/SlipGeneration/SlipGenerationCommandHandler.php), [`BillingPolicyResolver`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/BillingPolicy/Domain/Service/BillingPolicyResolver.php)
- **Test**: `tests/Context/Slip/Application/UseCase/SlipGeneration/SlipGenerationCommandHandlerTest.php`, `tests/Context/BillingPolicy/Domain/Service/BillingPolicyResolverTest.php`

### `atom-21-glm-03-pf1se-splits-60000-syndic-and-remainder-to-unit-401` (`GLM-03`)
- **Spec**: `SPEC-GLM` | **Nivel**: `unit` | **Modo**: `characterization`
- **Dado**: Un gasto o recurrente con código `PF1SE` de `67000` céntimos y unidades activas entre las que está la unidad `401`.
- **Cuando**: Se ejecuta `SyndicFeeSlipPoolAdjustmentService::adjust(...)`.
- **Entonces**: Asigna `60000` céntimos al pool igual del síndico (`syndicEqualPoolCents`), `7000` céntimos (`internetShareCents`) como gasto individual de la unidad `401`, y descuenta los `67000` del pool igual base.
- **Cubre**: [`SyndicFeeSlipPoolAdjustmentService`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Slip/Domain/Service/SyndicFeeSlipPoolAdjustmentService.php)
- **Test**: `tests/Context/Slip/Domain/Service/SyndicFeeSlipPoolAdjustmentServiceTest.php`

### `atom-22-glm-04-record-and-resolve-billing-policy-month-integration` (`GLM-04`)
- **Spec**: `SPEC-GLM` | **Nivel**: `integration` | **Modo**: `tdd`
- **Dado**: Un síndico autenticado.
- **Cuando**: Registra la política de un mes vía `PUT /api/v1/billing-policy/{yearMonth}` y luego la consulta vía `GET /api/v1/billing-policy/resolve?targetMonth={yearMonth}`.
- **Entonces**: Persiste el snapshot y el evento `MonthlyBillingParametersWereRecorded`, y la resolución devuelve exactamente esos importes en céntimos.
- **Cubre**: [`BillingPolicyMonthPutController`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/BillingPolicy/Infrastructure/Http/Controller/BillingPolicyMonthPutController.php), [`BillingPolicyResolveGetController`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/BillingPolicy/Infrastructure/Http/Controller/BillingPolicyResolveGetController.php)
- **Test**: `tests/Context/BillingPolicy/Infrastructure/Http/Controller/BillingPolicyControllersTest.php`

---

## Etapa 6 (`stage-06`) — `SPEC-CIE`: Cierre de periodo

### `atom-23-cie-01-close-month-without-slips-throws` (`CIE-01`)
- **Spec**: `SPEC-CIE` | **Nivel**: `unit` | **Modo**: `characterization`
- **Dado**: Un mes de gasto abierto $(Y, M)$ para cuyo mes de vencimiento $(M+1)$ no existen cuotas generadas (`findByMonthYear === []`).
- **Cuando**: Se ejecuta `ClosePeriodCommandHandler`.
- **Entonces**: Lanza `NoSlipsToCloseException` y no persiste ningún `PeriodClosure`.
- **Cubre**: [`ClosePeriodCommandHandler`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Slip/Application/UseCase/ClosePeriod/ClosePeriodCommandHandler.php)
- **Test**: `tests/Context/Slip/Application/UseCase/ClosePeriod/ClosePeriodCommandHandlerTest.php`

### `atom-24-cie-02-close-already-closed-month-throws` (`CIE-02`)
- **Spec**: `SPEC-CIE` | **Nivel**: `unit` | **Modo**: `characterization`
- **Dado**: Un mes de gasto $(Y, M)$ que ya figura como cerrado en `PeriodClosureRepository`.
- **Cuando**: Se ejecuta `PeriodClosureGuard::assertNotClosed(Y, M)` o `ClosePeriodCommandHandler`.
- **Entonces**: Lanza `PeriodAlreadyClosedException`.
- **Cubre**: [`PeriodClosureGuard`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Slip/Domain/Service/PeriodClosureGuard.php), [`ClosePeriodCommandHandler`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Slip/Application/UseCase/ClosePeriod/ClosePeriodCommandHandler.php)
- **Test**: `tests/Context/Slip/Domain/Service/PeriodClosureGuardTest.php`, `tests/Context/Slip/Application/UseCase/ClosePeriod/ClosePeriodCommandHandlerTest.php`

### `atom-25-cie-03-generate-slips-after-period-closed-fails-integration` (`CIE-03`)
- **Spec**: `SPEC-CIE` | **Nivel**: `integration` | **Modo**: `tdd`
- **Dado**: Un mes de gasto $(Y, M)$ con cuotas generadas en $(M+1)$ que ha sido cerrado vía `POST /api/v1/slips/close-period` (emitiendo `PeriodWasClosed`).
- **Cuando**: Se intenta volver a generar cuotas para ese mismo mes $(Y, M)$ vía `POST /api/v1/slips/generate` (incluso con `force: true`).
- **Entonces**: La petición es rechazada por `PeriodClosureGuard` con `PeriodAlreadyClosedException` (`409 Conflict` / error de dominio) y las cuotas existentes no se sobrescriben.
- **Cubre**: [`ClosePeriodPostController`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Slip/Infrastructure/Http/Controller/ClosePeriodPostController.php), [`SlipGenerationPostController`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Slip/Infrastructure/Http/Controller/SlipGenerationPostController.php)
- **Test**: `tests/Context/Slip/Infrastructure/Http/Controller/ClosePeriodAndGenerationGuardControllerTest.php`

---

## Etapa 7 (`stage-07`) — `SPEC-BAN` y `SPEC-LEC`: Extracto OFX, Previsión y Libro

### `atom-26-ban-01-bank-import-line-xor-expense-income` (`BAN-01`)
- **Spec**: `SPEC-BAN` | **Nivel**: `unit` | **Modo**: `tdd`
- **Dado**: Parámetros para construir `BankTransactionImport`.
- **Cuando**: Se intenta instanciar sin `expenseId` ni `incomeId` (`null, null`) o con ambos a la vez (`'exp-1', 'inc-1'`).
- **Entonces**: Lanza `InvalidArgumentException` (`BankTransactionImport must reference exactly one of expenseId or incomeId.`); y cuando se pasa exactamente uno de los dos, construye la entidad correctamente.
- **Cubre**: [`BankTransactionImport`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/BankStatement/Domain/BankTransactionImport.php)
- **Test**: `tests/Context/BankStatement/Domain/BankTransactionImportTest.php`

### `atom-27-ban-02-ofx-confirm-idempotency-key` (`BAN-02`)
- **Spec**: `SPEC-BAN` | **Nivel**: `integration` | **Modo**: `characterization`
- **Dado**: Una línea de extracto OFX confirmada con su clave de línea.
- **Cuando**: Se verifica/confirma el extracto en `OfxConfirmPostController` / `DoctrineBankTransactionImportRepository`.
- **Entonces**: Queda registrado el movimiento sin duplicar importaciones de la misma clave de línea.
- **Cubre**: [`OfxConfirmPostController`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/BankStatement/Infrastructure/Http/Controller/OfxConfirmPostController.php), [`DoctrineBankTransactionImportRepository`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/BankStatement/Infrastructure/Persistence/Doctrine/DoctrineBankTransactionImportRepository.php)
- **Test**: `tests/Context/BankStatement/Infrastructure/Http/Controller/OfxConfirmPostControllerTest.php`

### `atom-28-lec-01-ledger-empty-month-returns-empty-list` (`LEC-01`)
- **Spec**: `SPEC-LEC` | **Nivel**: `integration` | **Modo**: `tdd`
- **Dado**: Un mes $(Y, M)$ sin gastos ni ingresos activos en la base de datos.
- **Cuando**: Se consulta `GET /api/v1/ledger/movements?year={Y}&month={M}`.
- **Entonces**: Responde `200 OK` con `{"month": "YYYY-MM", "movements": []}`.
- **Cubre**: [`LedgerMovementsGetController`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Ledger/Infrastructure/Http/Controller/LedgerMovementsGetController.php), [`FindLedgerMovementsQueryHandler`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Ledger/Application/UseCase/FindLedgerMovements/FindLedgerMovementsQueryHandler.php)
- **Test**: `tests/Context/Ledger/Infrastructure/Http/Controller/LedgerMovementsGetControllerTest.php`

### `atom-29-lec-02-forecast-returns-target-month` (`LEC-02`)
- **Spec**: `SPEC-LEC` | **Nivel**: `integration` | **Modo**: `characterization`
- **Dado**: Unidades residenciales activas en el condominio.
- **Cuando**: Se consulta `GET /api/v1/forecast?targetMonth=2026-01`.
- **Entonces**: Responde `200 OK` con `targetMonth: "2026-01"` y la estructura técnica de previsión.
- **Cubre**: [`ForecastGetController`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Context/Forecast/Infrastructure/Http/Controller/ForecastGetController.php)
- **Test**: `tests/Context/Forecast/Infrastructure/Http/Controller/ForecastGetControllerTest.php`

---

## Etapa 8 (`stage-08`) — Blindaje Estándar de la Industria (Higiene de Suites, Arquitectura y Contratos)

### `atom-30-ind-01-suite-isolation-and-phpunit-tiers` (`IND-01`)
- **Spec**: `TESTING-STANDARD-INDUSTRIA` | **Nivel**: `unit` / `integration` | **Modo**: `tdd`
- **Dado**: `CatalogSeederTest` ubicado en `tests/Context/Setup/Application/Service/` extendiendo `ApiTestCase` y `phpunit.xml.dist` sin suite `acceptance` ni `architecture`.
- **Cuando**: Se desacopla `CatalogSeederTest` como test unitario puro con dobles (o se mueve el test de integración de BD a `Infrastructure/Persistence/CatalogSeederIntegrationTest.php`) y se registran las suites `unit`, `architecture`, `shared`, `authenticated_integration` y `acceptance` en `phpunit.xml.dist`.
- **Entonces**: `vendor/bin/phpunit --testsuite=unit` ejecuta el 100% de los tests unitarios en $< 5\text{ s}$ con **cero conexiones a base de datos**.
- **Cubre**: [`phpunit.xml.dist`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/phpunit.xml.dist), [`CatalogSeederTest`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/tests/Context/Setup/Application/Service/CatalogSeederTest.php)
- **Test**: `tests/Context/Setup/Application/Service/CatalogSeederTest.php`

### `atom-31-ind-02-hexagonal-architecture-fitness-tests` (`IND-02`)
- **Spec**: `TESTING-STANDARD-INDUSTRIA` | **Nivel**: `architecture` | **Modo**: `tdd`
- **Dado**: El árbol completo de clases en `src/Context/*/Domain`, `src/Context/*/Application` y `src/Context/*/Infrastructure`.
- **Cuando**: Se ejecuta `tests/Architecture/HexagonalArchitectureTest.php`.
- **Entonces**: Verifica automáticamente que:
  1. Ninguna clase en `Domain` importa namespaces de `Application`, `Infrastructure`, `Doctrine\ORM` ni `Symfony\Component\HttpFoundation`.
  2. Ninguna clase en `Application` importa clases de `Infrastructure`.
  3. Todos los eventos en `Domain/Event` (o `Domain/Bus`) son `readonly` y extienden `DomainEvent`.
- **Cubre**: `src/Context/**`
- **Test**: `tests/Architecture/HexagonalArchitectureTest.php`

### `atom-32-ind-03-openapi-and-security-boundary-contracts` (`IND-03`)
- **Spec**: `TESTING-STANDARD-INDUSTRIA` | **Nivel**: `integration` / `contract` | **Modo**: `tdd`
- **Dado**: El kernel de Symfony y las rutas de todos los Bounded Contexts.
- **Cuando**: Se consulta `GET /api/v1/doc.json` y se auditan las fronteras de `SetupRequiredListener` y `SyndicWriteAuthorizationTest`.
- **Entonces**: El contrato OpenAPI 3.0 se genera sin errores (`200 OK`), incluye los paths `/api/v1/*` etiquetados por Bounded Context, y la matriz de autorización rechaza con `401`/`403` cualquier acceso no autorizado.
- **Cubre**: [`BoundedContextRouteDescriber`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Shared/Infrastructure/OpenApi/BoundedContextRouteDescriber.php), [`SetupRequiredListener`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/src/Shared/Infrastructure/SetupRequiredListener.php)
- **Test**: `tests/Shared/Infrastructure/OpenApiContractTest.php`

---

## Matriz de Bloqueo (`por-hacer.md`) — Prohibido Abrir Átomos

De acuerdo con [`docs/specs/por-hacer.md`](file:///home/luisriego/Dev/PHP/Matisse.Api.Sf/docs/specs/por-hacer.md), las siguientes especificaciones están **bloqueadas** o **descartadas** y **no generan átomos en `stages.json`** hasta que cambie su estado:

| Spec | Estado | Razón de Bloqueo |
|---|---|---|
| **`SPEC-BOL`** | `bloqueada` | Falta el contrato de la API bancaria para alta de boleto, baja e identificación de impago (`overdue`). El circuito de aprobación `validated`/`posted`/`rejected` de `docs/NEXTSTEPS.md` queda **descartado**. |
| **`SPEC-ABR` (`ABR-01`..`ABR-04`)** | `bloqueada` | Decisiones pendientes: (1) regla de reparto de síndico cuando coexisten setup y política; (2) permisos y auditoría de `force` en meses pasados; (3) inmutabilidad o corrección de `paidAt` en gastos; (4) paso de transporte `async` de `sync://` a cola real. |
| **`SPEC-LEC-PROMESA`** | `bloqueada` | Falta definir la igualdad matemática prometida entre `Forecast` y `SPEC-INF`, y si `Ledger` incluye o no las cuotas. |
| **`SPEC-RAT`** | `bloqueada` | Frases inferidas del código pendientes de ratificación formal (campos de `Income` desde banco, unicidad de `RecurringExpense` mensual, convención `PF1SE`). |
