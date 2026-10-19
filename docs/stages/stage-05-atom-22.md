# Work Card: `atom-22-glm-04-record-and-resolve-billing-policy-month-integration` (GLM-04)

- **Stage**: `stage-05` — SPEC-GLM: Gas y politica del mes
- **Spec**: `SPEC-GLM` (`GLM-04`)
- **Level**: `integration`
- **Execution Mode**: `tdd`
- **Branch**: `atom-22-glm-04-record-and-resolve-billing-policy-month-integration` (from `stage-05`)

---

## 1. Regla Comprobable (BDD Specification)

```yaml
ID: GLM-04
Spec: SPEC-GLM
Nivel: integration
Modo: tdd
Dado: un sindico autenticado
Cuando: guarda los parametros del mes con PUT /api/v1/billing-policy/{yearMonth} y los resuelve con GET /api/v1/billing-policy/resolve?targetMonth={yearMonth}
Entonces: la respuesta de resolve contiene hasPolicy=true y exactamente los mismos importes en centimos
Cubre: src/Context/BillingPolicy/Infrastructure/Http/Controller/BillingPolicyMonthPutController.php
Test: tests/Context/BillingPolicy/Infrastructure/Http/Controller/BillingPolicyControllersTest.php
```

---

## 2. Alcance Permitido (Allowlist SRP)

Solo se permite crear o modificar los siguientes archivos durante este átomo:

- `src/Context/BillingPolicy/Infrastructure/Http/Controller/BillingPolicyMonthPutController.php`
- `src/Context/BillingPolicy/Infrastructure/Http/Controller/BillingPolicyResolveGetController.php`
- `tests/Context/BillingPolicy/Infrastructure/Http/Controller/BillingPolicyControllersTest.php`

---

## 3. Ciclo Cleanroom (`Red → Green → Refactor → Gate → Seal`)

1. **Red / Characterization**:
   - Si el modo es `tdd`: escribir primero el test en `tests/Context/BillingPolicy/Infrastructure/Http/Controller/BillingPolicyControllersTest.php` y verificar que falla por la ausencia de comportamiento.
   - Si el modo es `characterization`: escribir o aislar el caso específico de `GLM-04` en `tests/Context/BillingPolicy/Infrastructure/Http/Controller/BillingPolicyControllersTest.php` verificando la invariante exacta de dominio/infraestructura.
2. **Green**:
   - Implementar el cambio mínimo necesario en `src/Context/BillingPolicy/Infrastructure/Http/Controller/BillingPolicyMonthPutController.php` respetando SRP, DRY y Arquitectura Hexagonal (`declare(strict_types=1);`).
3. **Refactor**:
   - Limpiar duplicación usando Object Mothers en `tests/Context/*/Domain/*Mother.php` y garantizar nivel máximo en PHPStan.
4. **Gate & Seal**:
   ```bash
   ./tools/cleanroom/gate.sh stage-05 atom-22-glm-04-record-and-resolve-billing-policy-month-integration
   ```
